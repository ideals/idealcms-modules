<?php
namespace Shop\Structure\Service\Load1CV3\Db\Order;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Shop\Structure\Service\Load1CV3\Db\AbstractDb;
use Shop\Structure\Service\Load1CV3\Db\Good\DbGood;

class DbOrderAbstract extends AbstractDb
{
    /** Структура для получения prev_structure */
    protected string $structurePart = 'ideal_structure_datalist';

    /** Предыдущая категория для prev_structure */
    protected string $prevCat;

    /** Название таблицы, которая содержит детальную информацию о заказе */
    protected string $detailedTable;

    /** 1С ключи для точечной выборки заказов из базы. */
    protected array $orderKeys;

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
            'SELECT ID FROM ' . $this->structurePart . ' WHERE structure = "Shop_Order" LIMIT 1'
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
        $sql = "SELECT * FROM $this->table WHERE $orderKeysWhere";
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
        $sql = "SELECT * FROM $table WHERE order_id IN (" . implode(',', $orderIds) . ')';
        $goods = $db->select($sql);

        // Перестраиваем массив с заказами, чтобы ключами были идентификаторы из 1C
        $kOrders = [];
        foreach ($orders as $k => $order) {
            $order['goods'] = [];

            // В 1С в дате создания заказа нет времени, поэтому отрезаем его
            $order['date_create'] = date('Y-m-d', $order['date_create']);

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

        return $kOrders;
    }

    /**
     * Подготовка временной таблицы для выгрузки
     */
    public function prepareTable()
    {
        $this->dropTestTable();
        $this->createEmptyTestTable();
        $this->copyOrigTable();
    }

    /**
     * Обмен временной и оригинальной таблицы
     */
    public function updateOrigTable()
    {
        $db = Db::getInstance();

        $testTable = $this->table . $this->tablePostfix;
        $sql = "RENAME TABLE $this->table TO {$this->table}_tmp,
             $testTable TO $this->table,
             {$this->table}_tmp TO $testTable";
        $db->query($sql);

        $testTable = $this->detailedTable . $this->tablePostfix;
        $sql = "RENAME TABLE $this->detailedTable TO {$this->detailedTable}_tmp,
             $testTable TO $this->detailedTable,
             {$this->detailedTable}_tmp TO $testTable";
        $db->query($sql);
    }

    /**
     * Удаление временной таблицы
     */
    public function dropTestTable()
    {
        $db = Db::getInstance();
        $db->query('DROP TABLE IF EXISTS ' . $this->table . $this->tablePostfix);
        $db->query('DROP TABLE IF EXISTS ' . $this->detailedTable . $this->tablePostfix);
    }

    /**
     * @param array $orderKeys
     */
    public function setOrderKeys($orderKeys)
    {
        $this->orderKeys = $orderKeys;
    }

    /**
     * Заменяет идентификатор товара в заказе на новый
     *
     * @param array $good Актуальная информация
     * @param array $goods Список устаревших товаров
     */
    public function changeGoodInOrder($good, $goods)
    {
        // Собираем идентификаторы товаров для замены
        $changeIds = [];
        foreach ($goods as $goodItem) {
            if (isset($goodItem['ID'])) {
                $changeIds[] = $goodItem['ID'];
            }
        }

        if ($changeIds) {
            $db = Db::getInstance();

            // Удаляем сами товары
            $whereId = implode(',', $changeIds);
            $sql = "UPDATE $this->detailedTable$this->tablePostfix SET good_id = {$good['ID']}"
                . " WHERE good_id IN ($whereId)";
            $db->query($sql);
        }
    }

    /**
     * Создание временной таблицы для сохранения данных со схемой оригинальной таблицы
     */
    protected function createEmptyTestTable()
    {
        $db = Db::getInstance();
        $db->query(
            'CREATE TABLE ' . $this->table . $this->tablePostfix . ' LIKE ' . $this->table
        );
        $db->query(
            'CREATE TABLE ' . $this->detailedTable . $this->tablePostfix . ' LIKE ' . $this->detailedTable
        );
    }

    /**
     * Копирование данных из оригинальной таблицы во временную
     */
    protected function copyOrigTable()
    {
        parent::copyOrigTable();
        $db = Db::getInstance();

        $testTable = $this->detailedTable . $this->tablePostfix;
        $db->query("INSERT INTO $testTable SELECT * FROM $this->detailedTable");
    }

    public function insert($element)
    {
        $element['structure'] = 'Shop_OrderPay';
        $element['export'] = 0;

        $element['price'] = str_replace(',', '.', $element['price']) * 100;

        $goods = $element['goods'];
        unset($element['goods']);

        $orderId = parent::insert($element);

        $this->saveGoods($orderId, $goods, true);

        return $orderId;
    }

    public function update($element, $oldElement = null)
    {
        $this->saveGoods($element['ID'], $element['goods'], false);
        unset($element['goods']);

        $element['price'] *= 100;

        parent::update($element);
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
     * @return array Обработанный список товаров заказа
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
            if (isset($goodIdExploded[1])) {
                $good['offer_id_1c'] = $goodIdExploded[1];
            } else {
                $good['offer_id_1c'] = $good['good_id_1c'];
            }
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
