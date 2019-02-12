<?php
namespace Shop\Structure\Service\Load1CV3\Db\Order;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Shop\Structure\Service\Load1CV3\Db\AbstractDb;
use Shop\Structure\Service\Load1CV3\Db\Good\DbGood;

class DbOrderAbstract extends AbstractDb
{
    /** @var string Структура для получения prev_structure */
    protected $structurePart = 'ideal_structure_datalist';

    /** @var string Предыдущая категория для prev_structure */
    protected $prevCat;

    /** @var string Все неприсвоенные категориям товары будут лежать тут */
    public $defaultCategory = '';

    /** @var string Название таблицы, которая содержит детальную информацию о заказе */
    protected $detailedTable;

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
            'SELECT ID FROM ' . $this->structurePart . ' WHERE structure = "Shop_Order" LIMIT 1'
        );
        $this->prevCat = '3-' . $res[0]['ID'];
    }

    /**
     * Получение массива заказов из базы данных
     *
     * @return array ключ - orderId1c, значение - массив данных с детальной информацией по заказу.
     */
    public function parse($fields)
    {
        $db = Db::getInstance();
        $config = Config::getInstance();

        $orderKeysWhere = '';
        if ($this->orderKeys) {
            $orderKeysWhere = "'" . implode("','", $this->orderKeys) . "'";
            $orderKeysWhere = ' orderId1c IN (' . $orderKeysWhere . ')';
        }

        // Считываем заказы из нашей БД
        $sql = "SELECT * FROM {$this->table} WHERE {$orderKeysWhere}";
        $orders = $db->select($sql);

        if (empty($orders)) {
            return array();
        }

        // Собираем список идентификаторов заказов
        $orderIds = array();
        foreach ($orders as $order) {
            $orderIds[] = $order['ID'];
        }

        // Считываем все товары для наших заказов
        $table = $config->db['prefix'] . 'shop_structure_orderdetail';
        $sql = "SELECT * FROM {$table} WHERE order_id IN (" . implode(',', $orderIds) . ')';
        $goods = $db->select($sql);

        // Перестраиваем массив с заказами, чтобы ключами были идентификаторы из 1C
        $kOrders = array();
        foreach ($orders as $k => $order) {
            $order['goods'] = array();
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
     *
     * @param $onlyUpdate bool Файл Содержит Только Обновления
     */
    public function prepareTable($onlyUpdate)
    {
        $this->onlyUpdate = $onlyUpdate;
        $this->dropTestTable();
        $this->createEmptyTestTable();
        $this->copyOrigTable();
    }

    /**
     * Свап временной и оригинальной таблицы
     */
    public function updateOrigTable()
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
    public function dropTestTable()
    {
        $db = Db::getInstance();

        $testTable = $this->table . $this->tablePostfix;
        $sql = "show tables like '{$testTable}'";
        $result = $db->query($sql);
        $res = $result->fetch_all(MYSQLI_ASSOC);
        if (count($res) > 0) {
            $sql = "DROP TABLE {$testTable}";
            $db->query($sql);
        }

        $testTable = $this->detailedTable . $this->tablePostfix;
        $sql = "show tables like '{$testTable}'";
        $result = $db->query($sql);
        $res = $result->fetch_all(MYSQLI_ASSOC);
        if (count($res) > 0) {
            $sql = "DROP TABLE {$testTable}";
            $db->query($sql);
        }
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
        $changeIds = array();
        foreach ($goods as $goodItem) {
            if (isset($goodItem['ID'])) {
                $changeIds[] = $goodItem['ID'];
            }
        }

        if ($changeIds) {
            $db = Db::getInstance();

            // Удаяем сами товары
            $whereId = implode(',', $changeIds);
            $sql = "UPDATE {$this->detailedTable}{$this->tablePostfix} SET good_id = {$good['ID']}";
            $sql .= " WHERE good_id IN ({$whereId})";
            $db->query($sql);
        }
    }

    /**
     * Создание временной таблицы для сохранения данных со схемой оригинальной таблицы
     */
    protected function createEmptyTestTable()
    {
        $db = Db::getInstance();
        $testTable = $this->table . $this->tablePostfix;

        $sql = "CREATE TABLE {$testTable} LIKE {$this->table}";
        $db->query($sql);

        $testTable = $this->detailedTable . $this->tablePostfix;

        $sql = "CREATE TABLE {$testTable} LIKE {$this->detailedTable}";
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
        $sql = "INSERT INTO {$testTable} SELECT * FROM {$this->detailedTable}";
        $db->query($sql);
    }

    public function insert($element)
    {
        $element['structure'] = 'Shop_OrderDetail';
        $element['export'] = 0;

        $goods = $element['goods'];
        unset($element['goods']);

        $orderId = parent::insert($element);

        $this->saveGoods($orderId, $goods, true);

        return $orderId;
    }

    public function update($element)
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
               ->where('order_id=:order_id', array('order_id' => $id))
               ->exec();

        }

        $goods = $this->prepareGoodsForSave($id, $goods);
        if (!empty($goods)) {
            $db->insertMultiple($goodsTable, $goods);
        }
    }

    protected function prepareGoodsForSave($id, $goods)
    {
        foreach ($goods as $goodId => &$good) {
            $good['sum'] *= 100;
            $good['price'] *= 100;
            $good['discount'] = empty($good['discount']) ? 0 : $good['discount'] * 100;
            // todo получение ID структуры Order
            $good['prev_structure'] = '12-' . $id;
            $good['order_id'] = $id;
            $goodIdExploded = explode('#', $good['good_id_1c']);
            $good['good_id_1c'] = $goodIdExploded[0];
            if (!isset($goodIdExploded[1])) {
                $good['offer_id_1c'] = $good['good_id_1c'];
            } else {
                $good['offer_id_1c'] = $goodIdExploded[1];
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
