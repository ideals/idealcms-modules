<?php

namespace Shop\Structure\Service\Load1CV208\Db\Order;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Shop\Structure\Service\Load1CV208\Db\AbstractDb;
use Shop\Structure\Service\Load1CV208\Db\Good\DbGood;

class DbOrderAbstract extends AbstractDb
{
    /** @var string Все неприсвоенные категориям товары будут лежать тут */
    public $defaultCategory = '';

    /** @var string Структура для получения prev_structure */
    protected string $structurePart = 'ideal_structure_datalist';

    /** @var string Предыдущая категория для prev_structure */
    protected string $prevCat;

    /** @var string Название таблицы, которая содержит детальную информацию о заказе */
    protected string $detailedTable;

    /** @var array 1С ключи для точечной выборки заказов из базы. */
    protected $orderKeys;

    /**
     *  Установка полей класса - полного имени таблиц с префиксами и получения prev_structure
     */
    public function __construct()
    {
        parent::__construct();
        $db = Db::getInstance();
        $this->table = $this->prefix . 'shop_structure_order';
        $this->detailedTable = $this->prefix . 'shop_structure_orderdetail';
        $this->structurePart = $this->prefix . $this->structurePart;
        $res = $db->select(
            'SELECT ID FROM ' . $this->structurePart . ' WHERE structure = "Shop_Order" LIMIT 1',
        );
        $this->prevCat = '3-' . $res[0]['ID'];
    }

    /**
     * Получение массива заказов из базы данных
     *
     * @return array ключ - orderId1c, значение - массив данных с детальной информацией по заказу.
     */
    public function parse($fields): array
    {
        $db = Db::getInstance();
        $config = Config::getInstance();

        $orderKeysWhere = '';
        if ($this->orderKeys) {
            $orderKeysWhere = "'" . implode("','", $this->orderKeys) . "'";
            $orderKeysWhere = ' orderId1c IN (' . $orderKeysWhere . ')';
        }

        // Считываем заказы из нашей БД
        $sql = sprintf('SELECT * FROM %s WHERE %s', $this->table, $orderKeysWhere);
        $orders = $db->select($sql);

        if (empty($orders)) {
            return [];
        }

        // Собираем список идентификаторов заказов
        $orderIds = [];
        foreach ($orders as $order) {
            $orderIds[] = $order['ID'];
        }

        // Считываем все товары для наших заказов
        $table = $config->db['prefix'] . 'shop_structure_orderdetail';
        $sql = sprintf('SELECT * FROM %s WHERE order_id IN (', $table) . implode(',', $orderIds) . ')';
        $goods = $db->select($sql);

        // Перестраиваем массив с заказами, чтобы ключами были идентификаторы из 1C
        $kOrders = [];
        foreach ($orders as $k => $order) {
            $order['goods'] = [];
            $kOrders[$order['orderId1c']] = $order;
            unset($orders[$k]);
        }

        // Добавляем товары к каждому заказу
        foreach ($kOrders as &$order) {
            foreach ($goods as $k => $good) {
                if ($good['order_id'] == $order['ID']) {
                    $order['goods'][] = $good;
                    unset($goods[$k]);
                }
            }
        }

        /*
        // Удаляем ненужные поля (которых нет в xml-файле)
        foreach ($kOrders as &$kOrder) {
            foreach ($kOrder as $k => $v) {
                // Если поле не установлено в xml-выгрузке, убираем его
                if (!isset($fields[$k])) {
                    unset($kOrder[$k]);
                }
                if (!is_array($v)) {
                    continue;
                }
                foreach ($v as $i => $item) {
                    if (!isset($fields[$k])) {
                        unset($kOrder[$k]);
                    }
                }
            }
        }
        */

        return $kOrders;
    }

    /**
     * Подготовка временной таблицы для выгрузки
     */
    public function prepareTable(): void
    {
        $this->dropTestTable();
        $this->createEmptyTestTable();
        $this->copyOrigTable();
    }

    /**
     * Свап временной и оригинальной таблицы
     */
    public function updateOrigTable(): void
    {
        $db = Db::getInstance();

        $testTable = $this->table . $this->tablePostfix;
        $sql = "RENAME TABLE {$this->table} TO {$this->table}_tmp,
             {$testTable} TO {$this->table},
             {$this->table}_tmp TO {$testTable}";
        $db->query($sql);

        $testTable = $this->detailedTable . $this->tablePostfix;
        $sql = "RENAME TABLE {$this->detailedTable} TO {$this->detailedTable}_tmp,
             {$testTable} TO {$this->detailedTable},
             {$this->detailedTable}_tmp TO {$testTable}";
        $db->query($sql);
    }

    /**
     * Удаление временной таблицы
     */
    public function dropTestTable(): void
    {
        $db = Db::getInstance();

        $testTable = $this->table . $this->tablePostfix;
        $sql = sprintf("show tables like '%s'", $testTable);
        $result = $db->query($sql);
        $res = $result->fetch_all(MYSQLI_ASSOC);
        if (count($res) > 0) {
            $sql = 'DROP TABLE ' . $testTable;
            $db->query($sql);
        }

        $testTable = $this->detailedTable . $this->tablePostfix;
        $sql = sprintf("show tables like '%s'", $testTable);
        $result = $db->query($sql);
        $res = $result->fetch_all(MYSQLI_ASSOC);
        if (count($res) > 0) {
            $sql = 'DROP TABLE ' . $testTable;
            $db->query($sql);
        }
    }

    /**
     * @param array $orderKeys
     */
    public function setOrderKeys($orderKeys): void
    {
        $this->orderKeys = $orderKeys;
    }

    /**
     * Заменяет идентификатор товара в заказе на новый
     *
     * @param array<string, mixed> $good Актуальная информация
     * @param array $goods Список устаревших товаров
     */
    public function changeGoodInOrder(array $good, $goods): void
    {
        // Собираем идентификаторы товаров для замены
        $changeIds = [];
        foreach ($goods as $goodItem) {
            if (isset($goodItem['ID'])) {
                $changeIds[] = $goodItem['ID'];
            }
        }

        if ($changeIds !== []) {
            $db = Db::getInstance();

            // Удаяем сами товары
            $whereId = implode(',', $changeIds);
            $sql = sprintf('UPDATE %s%s SET good_id = %s', $this->detailedTable, $this->tablePostfix, $good['ID']);
            $sql .= sprintf(' WHERE good_id IN (%s)', $whereId);
            $db->query($sql);
        }
    }

    public function insert($element)
    {
        $element['structure'] = 'Shop_OrderDetail';
        $element['export'] = 0;

        $element['price'] = str_replace(',', '.', $element['price']) * 100;

        $goods = $element['goods'];
        unset($element['goods']);

        $orderId = parent::insert($element);

        $this->saveGoods($orderId, $goods, true);

        return $orderId;
    }

    public function update($element, $oldElement = null): void
    {
        $this->saveGoods($element['ID'], $element['goods'], false);
        unset($element['goods']);

        $element['price'] *= 100;

        parent::update($element);
    }

    /**
     * Создание временной таблицы для сохранения данных со схемой оригинальной таблицы
     */
    protected function createEmptyTestTable()
    {
        $db = Db::getInstance();
        $testTable = $this->table . $this->tablePostfix;

        $sql = sprintf('CREATE TABLE %s LIKE %s', $testTable, $this->table);
        $db->query($sql);

        $testTable = $this->detailedTable . $this->tablePostfix;

        $sql = sprintf('CREATE TABLE %s LIKE %s', $testTable, $this->detailedTable);
        $db->query($sql);
    }

    /**
     * Копирование данных из оригинальной таблицы во временную
     */
    protected function copyOrigTable()
    {
        parent::copyOrigTable();
        $db = Db::getInstance();

        $testTable = $this->detailedTable . $this->tablePostfix;
        $sql = sprintf('INSERT INTO %s SELECT * FROM %s', $testTable, $this->detailedTable);
        $db->query($sql);
    }

    protected function saveGoods($id, $goods, $isNew)
    {
        $db = Db::getInstance();
        $goodsTable = $this->detailedTable . $this->tablePostfix;

        if (!$isNew) {
            // Удаляем товары заказа из временной таблицы, чтобы добавить их заново
            $db->delete($goodsTable)
               ->where('order_id=:order_id', ['order_id' => $id])
               ->exec();

        }

        $goods = $this->prepareGoodsForSave($id, $goods);
        if (!empty($goods)) {
            $db->insertMultiple($goodsTable, $goods);
        }
    }

    /**
     * Подготовка списка товаров заказа к сохранению в БД
     *
     * @param int $id ИД заказа
     * @param array $goods Список товаров заказа
     * @return array Обработанный списка товаров заказа
     */
    protected function prepareGoodsForSave($id, $goods)
    {
        foreach ($goods as &$good) {
            $good['sum'] *= 100;
            $good['price'] *= 100;
            $good['discount'] = empty($good['discount']) ? 0 : $good['discount'] * 100;
            // todo получение ID структуры Order
            $good['prev_structure'] = '12-' . $id;
            $good['order_id'] = $id;
            $goodIdExploded = explode('#', $good['good_id_1c']);
            $good['good_id_1c'] = $goodIdExploded[0];
            $good['offer_id_1c'] = $goodIdExploded[1] ?? $good['good_id_1c'];

            // Пытаемся получить идентификатор товара по его 1с_id
            $good['good_id'] = 0;
            $dbGood = new dbGood();
            $dbGoodInfo = $dbGood->getGoods('ID, id_1c', 'id_1c = "' . $good['good_id_1c'] . '"');
            if (isset($dbGoodInfo[$good['good_id_1c']])) {
                $good['good_id'] = $dbGoodInfo[$good['good_id_1c']]['ID'];
            }
        }

        return $goods;
    }
}
