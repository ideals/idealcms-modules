<?php
namespace Shop\Structure\Service\Load1CV3\Db\Order;

use Shop\Structure\Service\Load1CV3\Db\AbstractDb;
use Ideal\Field\Url;
use Ideal\Core\Config;
use Ideal\Core\Db;
use Cabinet\Structure\User\Model as UserModel;
use Shop\Structure\Service\Load1CV3\Db\Good\DbGood;

class DbOrder extends AbstractDb
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
    public function parse()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();

        $orderKeysWhere = '';
        if ($this->orderKeys) {
            $orderKeysWhere = '\'' . implode('\',\'', $this->orderKeys) . '\'';
            $orderKeysWhere = ' AND sso.orderId1c IN (' . $orderKeysWhere . ')';
        }

        // Считываем заказы из нашей БД
        $i = $config->db['prefix'];
        $orderSql = <<<ORDERSQL
          SELECT 
            sso.id, 
            sso.date_create,
            sso.name,
            sso.price,
            sso.order_comment,
            sso.delivery_method,
            sso.delivery_phone,
            sso.delivery_country,
            sso.delivery_city,
            sso.delivery_address,
            sso.orderId1c,
            ssod.good_id_1c,
            ssod.offer_id_1c,
            ssod.count,
            ssod.sum,
            ssod.currency,
            ssod.name as full_name,
            ssod.coefficient,
            ssod.price as fe, 
            ssod.discount as discount
            FROM {$i}shop_structure_order as sso
            LEFT JOIN {$i}shop_structure_orderdetail as ssod ON ssod.order_id=sso.id
            WHERE sso.goods_id != ''{$orderKeysWhere}
ORDERSQL;

        $orderList = $db->select($orderSql);

        $result = array();
        // Собираем все детали заказа в одном месте
        foreach ($orderList as $element) {
            if ($element['good_id_1c'] == $element['offer_id_1c']) {
                $secondLvlKey = $element['good_id_1c'];
            } else {
                $secondLvlKey = $element['good_id_1c'] . '#' . $element['offer_id_1c'];
            }
            if (!$element['orderId1c']) {
                $result[$element['id']][$secondLvlKey] = $element;
            } else {
                $result[$element['orderId1c']][$secondLvlKey] = $element;
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function save($elements)
    {
        $db = Db::getInstance();
        $url = new Url\Model();
        $userModel = new UserModel('0-14');
        foreach ($elements as $orderId => $element) {
            $generalOrderInfo = current($element);

            // Общая информация о заказе
            $generalOrderInfo['prev_structure'] = $this->prevCat;
            $generalOrderInfo['price'] = $generalOrderInfo['sum'] * 100;
            $generalOrderInfo['address'] = $generalOrderInfo['delivery_address'];
            $date = \DateTime::createFromFormat('Y-m-d', $generalOrderInfo['date_create']);
            $generalOrderInfo['date_create'] = $date->getTimestamp();
            $generalOrderInfo['date_mod'] = $date->getTimestamp();

            // Если в инфорации о заказчике есть телефон приводим его к единому формату
            if (isset($generalOrderInfo['customer'][0]['phone']) && !empty($generalOrderInfo['customer'][0]['phone'])) {
                $phone = $generalOrderInfo['customer'][0]['phone'];
                $phone = preg_replace('/\D/', '', $phone);

                // Убираем код страны из телефонного номера (при его наличии)
                if (iconv_strlen($phone) > 10) {
                    $generalOrderInfo['customer'][0]['phone'] = substr($phone, -10);
                }
            }

            $generalOrderInfo['user_id'] = $userModel->getCustomerId($generalOrderInfo['customer']);

            // Убираем лишние поля для общей информации о заказе
            unset($generalOrderInfo['sum'], $generalOrderInfo['good_id'], $generalOrderInfo['count']);
            unset($generalOrderInfo['good_price'], $generalOrderInfo['good_sum'], $generalOrderInfo['currency']);
            unset($generalOrderInfo['name'], $generalOrderInfo['customer'], $generalOrderInfo['good_discount']);

            if (isset($element['update']) && $element['update'] === true) {
                // Если необходимо обновить данные заказа удаляем все данные деталей заказа
                $orderId = $element['orderId'];

                $db->delete($this->detailedTable . $this->tablePostfix)
                    ->where('order_id=:order_id', array('order_id' => $orderId))
                    ->exec();

                $db->update($this->table . $this->tablePostfix)
                    ->set($generalOrderInfo)
                    ->where('ID = :ID', array('ID' => $orderId))
                    ->exec();

                // Убираем элементы не относящиеся к детализации заказа
                unset($element['update'], $element['orderId']);
            } else {
                $generalOrderInfo['export'] = 0;
                $generalOrderInfo['structure'] = 'Shop_OrderDetail';
                $generalOrderInfo['name'] = 'temp';
                $generalOrderInfo['url'] = 'temp';
                $orderId = $db->insert($this->table . $this->tablePostfix, $generalOrderInfo);
                $additionalGeneralData['name'] = 'Заказ № ' . $orderId;
                $additionalGeneralData['url'] = $url->translitFileName($additionalGeneralData['name']);
                $db->update($this->table . $this->tablePostfix)
                    ->set($additionalGeneralData)
                    ->where('ID = :ID', array('ID' => $orderId))
                    ->exec();
            }

            $orderCount = 0;
            $goodsId = '';
            foreach ($element as $goodId => $detailedOrder) {
                $detailedOrder['prev_structure'] = '12-' . $orderId;
                $detailedOrder['order_id'] = $orderId;
                $goodIdExploded = explode('#', $goodId);
                $detailedOrder['good_id_1c'] = $goodIdExploded[0];
                if (!isset($goodIdExploded[1])) {
                    $detailedOrder['offer_id_1c'] = $detailedOrder['good_id_1c'];
                } else {
                    $detailedOrder['offer_id_1c'] = $goodIdExploded[1];
                }

                // Пытаемся получить идентификатор товара по его 1с_id
                $detailedOrder['good_id'] = 0;
                $dbGood = new dbGood();
                $dbGoodInfo = $dbGood->getGoods('ID, id_1c', 'id_1c = \'' . $detailedOrder['good_id_1c'] . '\'');
                if (isset($dbGoodInfo[$detailedOrder['good_id_1c']])) {
                    $detailedOrder['good_id'] = $dbGoodInfo[$detailedOrder['good_id_1c']]['ID'];
                }

                $detailedOrder['currency'] = 0;
                if (isset($detailedOrder['currency']) && $detailedOrder['currency'] !== 'RUB') {
                    $detailedOrder['currency'] = 1;
                }

                $detailedOrder['sum'] = $detailedOrder['good_sum'] * 100;
                $detailedOrder['price'] = $detailedOrder['good_price'] * 100;
                $detailedOrder['discount'] = 0;
                if ($detailedOrder['good_discount']) {
                    $detailedOrder['discount'] = $detailedOrder['good_discount'] * 100;
                }

                // Убираем лишние поля для сохранения деталей заказа
                unset($detailedOrder['orderId1c'], $detailedOrder['is_active'], $detailedOrder['date_create']);
                unset($detailedOrder['order_comment'], $detailedOrder['delivery_address']);
                unset($detailedOrder['good_price'], $detailedOrder['good_sum'], $detailedOrder['customer']);
                unset($detailedOrder['orderNumber1c'], $detailedOrder['good_discount']);

                $db->insert($this->detailedTable . $this->tablePostfix, $detailedOrder);
                $orderCount += (int)$detailedOrder['count'];
                if ($goodsId) {
                    $goodsId .= ',';
                }
                $goodsId .= $detailedOrder['good_id_1c'];
            }

            $table = $this->table . $this->tablePostfix;
            $sql = "UPDATE {$table} SET stock = {$orderCount}, goods_id = '{$goodsId}' WHERE ID = {$orderId}";
            $db->query($sql);
        }
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
}
