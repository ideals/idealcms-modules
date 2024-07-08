<?php
namespace Shop\Structure\Service\Load1CV3\Db\Good;

use Shop\Structure\Service\Load1CV3\Db\AbstractDb;
use Ideal\Core\Db;

class DbGoodAbstract extends AbstractDb
{
    /** @var string Структура для получения prev_structure */
    protected $structurePart = 'ideal_structure_part';

    /** @var string Предыдущая категория для prev_structure */
    public $prevGood;
    public $prevOffer;

    /** @var string Структуры категорий */
    protected $structureCat = 'catalogplus_structure_category';

    /** @var string Структуры категорий */
    protected $structureMedium = 'catalogplus_medium_categorylist';

    /** @var string Структуры категорий */
    protected $structureMediumTag = 'ideal_medium_taglist';

    /** @var array массив категорий с ID и id_1c */
    protected $categories;

    /** @var string Структура офферов */
    protected $offers = 'catalogplus_structure_offer';

    /** @var array 1С ключи для точечной выборки товаров из базы. */
    protected $goodKeys;

    /**
     *  Установка полей класса - полного имени таблиц с префиксами и получения prev_structure
     */
    public function __construct()
    {
        parent::__construct();
        $db = Db::getInstance();
        $this->table = $this->prefix . 'catalogplus_structure_good';
        $this->structurePart = $this->prefix . $this->structurePart;
        $this->structureCat = $this->prefix . $this->structureCat;
        $this->structureMedium = $this->prefix . $this->structureMedium;
        $this->structureMediumTag = $this->prefix . $this->structureMediumTag;
        $this->offers = $this->prefix . $this->offers;
        $res = $db->select(
            'SELECT ID FROM ' . $this->structurePart . ' WHERE structure = "CatalogPlus_Good" LIMIT 1'
        );
        $this->prevGood = '1-' . $res[0]['ID'];
    }

    /**
     * Парсинг товаров из БД
     *
     * @return array ключ - id_1c, значение - все необходимые поля (в SQL)
     */
    public function parse()
    {
        $db = Db::getInstance();

        $goodKeysWhere = '';
        if ($this->goodKeys) {
            $goodKeysWhere = '\'' . implode('\',\'', $this->goodKeys) . '\'';
            $goodKeysWhere = ' AND sg.id_1c IN (' . $goodKeysWhere . ')';
        }

        // Считываем товары из нашей БД
        $sql = 'SELECT sg.* FROM ' . $this->table . $this->tablePostfix .
            ' as sg WHERE sg.prev_structure=\'' . $this->prevGood . '\'' . $goodKeysWhere;

        $tmp = $db->select($sql);

        $result = [];
        foreach ($tmp as $element) {
            if ($element['id_1c'] === 'not-1c') {
                $result[] = $element;
            } else {
                $result[$element['id_1c']] = $element;
            }
        }

        return $result;
    }

    /**
     * Сохранение изменений и добавление новых товаров в БД
     *
     * @param array $goods массив товаров для сохранения
     */
    public function save($goods)
    {
        foreach ($goods as $k => $good) {
            if (!isset($good['prev_structure'])) {
                $goods[$k]['prev_structure'] = $this->prevGood;
            }
            $goods[$k]['structure'] = 'CatalogPlus_Offer';
        }

        parent::save($goods);
    }

    /**
     * Получение информации о товарах
     *
     * @param string $select
     * @param string $where
     * @return array key - id_1c
     */
    public function getGoods($select = '*', $where = '')
    {
        $db = Db::getInstance();

        $sql = "SELECT {$select} FROM " . $this->table . $this->tablePostfix;
        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }
        $res = $db->select($sql);
        $result = [];

        foreach ($res as $item) {
            $result[$item['id_1c']] = $item;
        }

        return $result;
    }

    /**
     * Обновление параметров товаров (stock, price, price_old) в конце выгрузки
     */
    public function updateGood()
    {
        $db = Db::getInstance();

        // Проверяем наличие тестовых таблиц, потому что их может не быть если происходит только лишь обмен заказами
        $result = $db->query('SHOW TABLES LIKE \'' . $this->offers . $this->tablePostfix . '\'');
        $res = $result->fetch_all(MYSQLI_ASSOC);
        if (count($res) === 0) {
            return;
        }

        $selectFields = 'ID, id_1c, price, offer_id_1c, stock, currency, price_old, possible_be_ordering_in_absence';
        $goods = $this->getGoods($selectFields, 'is_active = 1');

        $sql = 'SELECT ID as offer_id_1c, price, good_id as id_1c, currency, rest as stock, price_old FROM '
            . $this->offers . $this->tablePostfix . ' WHERE is_active = 1 ORDER BY good_id, price';

        $tmp = $db->select($sql);
        $result = [];
        foreach ($tmp as $item) {
            // Если оффер принадлежит исключённому товару, то пропускаем его
            if (!isset($goods[$item['id_1c']])) {
                continue;
            }
            // Если у предложения нет остатков, а товар нельзя заказать при отсутствии, то пропускаем этот оффер
            if ($item['stock'] == 0 && $goods[$item['id_1c']]['possible_be_ordering_in_absence'] != 1) {
                // Если у товара были остатки и не стало, то надо их убрать
                if (!isset($result[$item['id_1c']]) && $goods[$item['id_1c']]['stock'] > 0) {
                    $result[$item['id_1c']] = $item;
                }
                continue;
            }
            // Если товара ещё нет в массиве, то добавляем его
            if (!isset($result[$item['id_1c']])) {
                $result[$item['id_1c']] = $item;
            } else {
                $offer = $result[$item['id_1c']];
                if ((float) $item['price'] > 0 && ((float) $offer['price'] === 0 || $item['price'] < $offer['price'])) {
                    // Если товар уже есть в массиве, но у рассматриваемого оффера цена ниже, то обновляем цену у
                    // товара
                    $result[$item['id_1c']]['price'] = $item['price'];
                }

                // Складываем остатки всех офферов
                $result[$item['id_1c']]['stock'] += $item['stock'];

                if ((float)$item['price_old'] > 0 &&
                    ((float)$result[$item['id_1c']]['price_old'] == 0 ||
                        (float)$item['price_old'] > (float)$result[$item['id_1c']]['price_old']
                    )
                ) {
                    // Если товар уже есть в массиве, но у рассматриваемого оффера старая цена выше,
                    // то обновляем старую цену у товара
                    $result[$item['id_1c']]['price_old'] = (int)$item['price_old'];
                }
            }
        }

        $updates = [];
        foreach ($result as $k => $item) {
            if (!isset($goods[$k])) {
                continue;
            }
            $good = $goods[$k];
            $diff = array_diff_assoc($item, $good);
            if (count($diff) > 0) {
                // ID товара всегда в диффе
                $updates[$k] = $diff;
                $updates[$k]['ID'] = $good['ID'];
            }
        }
        parent::save($updates);
    }

    /**
     * @param array $goodKeys
     */
    public function setGoodKeys($goodKeys)
    {
        $this->goodKeys = $goodKeys;
    }

    /**
     * Подготовка параметров товара для добавления в БД
     *
     * @param array $element Добавляемый товар
     * @return array Модифицированный товар
     */
    protected function getForAdd($element)
    {
        $element['prev_structure'] = $this->prevGood;
        $element['structure'] = 'CatalogPlus_Offer';

        return parent::getForAdd($element);
    }

    /**
     * Удаляет информацию о товарах по всей базе
     *
     * @param array $goods Массив с информацией по товарам
     */
    protected function delGoods($goods)
    {
        // Удаляются только те товары, которые имеют идентификатор.
        // Собираем идентификаторы товаров для удаления
        $delIds = [];
        foreach ($goods as $good) {
            if (isset($good['ID'])) {
                $delIds[] = $good['ID'];
            }
        }

        if ($delIds) {
            $db = Db::getInstance();

            // Удаляем сами товары
            $whereId = implode(',', $delIds);
            $sql = "DELETE FROM {$this->table}{$this->tablePostfix} WHERE ID IN ({$whereId})";
            $db->query($sql);

            // Удаляем офферы
            [, $goodStructureId] = explode('-', $this->prevGood);
            $wherePrevStructure = '\'' . $goodStructureId . '-' . implode('\',\'' . $goodStructureId . '-', $delIds);
            $wherePrevStructure .= '\'';
            $sql = "DELETE FROM {$this->offers}{$this->tablePostfix} WHERE prev_structure IN ({$wherePrevStructure})";
            $db->query($sql);

            // удаляем теги товара
            $sql = "DELETE FROM {$this->structureMediumTag}{$this->tablePostfix} WHERE structure_id={$goodStructureId}";
            $sql .= " AND part_id IN ({$whereId})";
            $db->query($sql);

            // Удаляем отнесение к разделам каталога
            $sql = "DELETE FROM {$this->structureMedium}{$this->tablePostfix} WHERE good_id IN ({$whereId})";
            $db->query($sql);
        }
    }
}
