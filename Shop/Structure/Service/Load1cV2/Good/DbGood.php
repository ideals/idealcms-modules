<?php
namespace Shop\Structure\Service\Load1cV2\Good;

use Shop\Structure\Service\Load1cV2\AbstractDb;
use Ideal\Field\Url;
use Shop\Structure\Service\Load1cV2\Category;
use Ideal\Core\Db;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:31
 */

class DbGood extends AbstractDb
{
    /** @var string Структура для получения prev_structure */
    protected $structurePart = 'ideal_structure_part';

    /** @var string Предыдущая категория для prev_structure */
    protected $prevGood;

    /** @var string Структуры категорий */
    protected $structureCat = 'catalogplus_structure_category';

    /** @var string Структуры категорий */
    protected $structureMedium = 'catalogplus_medium_categorylist';

    /** @var array массив категорий с ID и id_1c */
    protected $categories;

    /** @var string Структура офферов */
    protected $offers = 'catalogplus_structure_offer';

    protected $goodToCat = array();

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

        // Считываем товары из нашей БД
        $sql = "SELECT sg.ID, sg.img, sg.imgs, sg.full_name, sg.name, sg.id_1c, sg.is_active,
            sg.url, sg.articul, sc.id_1c as category_id
            FROM " . $this->table . $this->tablePostfix .
            " as sg LEFT JOIN {$this->structureCat} as sc ON sg.category_id = sc.ID
            WHERE sg.prev_structure='{$this->prevGood}'";

        $tmp = $db->select($sql);

        $result = array();
        foreach ($tmp as $element) {
            if ($element['id_1c'] == 'not-1c') {
                $result[] = $element;
            } else {
                $result[$element['id_1c']] = $element;
            }
        }

        return $result;
    }

    public function truncateCategoryList()
    {
        $categoryModel = new Category\DbCategory();
        $this->categories = $categoryModel->getCategories();
        $db = Db::getInstance();

        if (!$this->onlyUpdate) {
            $sql = "TRUNCATE {$this->structureMedium}";
            $db->query($sql);
        }
    }

    public function updateCategoryList($goodToGroup)
    {
        $db = Db::getInstance();

        $result = array();
        $goods = $this->getGoods('ID, id_1c');
        foreach ($goodToGroup as $item) {
            if (!isset($goods[$item['good_id']])) {
                continue;
            }

            $categories = $this->getCategories($goods[$item['good_id']]['ID']);

            if (!in_array($this->categories[$item['category_id']], $categories)) {
                $result[] = array(
                    'good_id' => $goods[$item['good_id']]['ID'],
                    'category_id' => $this->categories[$item['category_id']]
                );
            }
        }

        while (count($result) > 24) {
            $part = array_splice($result, 0, 25);
            $db->insertMultiple($this->structureMedium, $part);
        }
    }

    /**
     * Сохранение изменений и добавление новых товаров в БД
     *
     * @param array $goods массив товаров для сохранения
     */
    public function save($goods)
    {
        foreach ($goods as $k => $good) {
            if (array_key_exists('category_id', $good)) {
                $goods[$k]['category_id'] = $this->categories[$good['category_id']];
            }

            $goods[$k]['imgs'] = (isset($good['imgs'])) ? $good['imgs'] : '';
        }

        parent::save($goods);
    }

    /**
     * Получение инфомрации о товарах
     *
     * @param string $select
     * @return array key - id_1c
     */
    public function getGoods($select = '*')
    {
        $db = Db::getInstance();

        $sql = "SELECT {$select} FROM " . $this->table . $this->tablePostfix;
        $res = $db->select($sql);
        $result = array();

        foreach ($res as $item) {
            $result[$item['id_1c']] = $item;
        }

        return $result;
    }

    public function updateGood()
    {
        $db = Db::getInstance();

        $sql = "SELECT ID as offer_id_1c, min(price) as price, good_id, currency, rest as stock "
            ."FROM " . $this->offers . $this->tablePostfix . " GROUP BY good_id";

        $result = array();
        $tmp = $db->select($sql);
        foreach ($tmp as $item) {
            $result[$item['good_id']] = $item;
        }
        $goods = $this->getGoods('ID, id_1c, price, offer_id_1c, stock, currency');

        $updates = array();
        foreach ($result as $k => $item) {
            if (isset($goods[$k])) {
                $diff = array_diff($item, $goods[$k]);
                if (count($diff) > 1) {
                    // ID товара всегда в диффе
                    $updates[$k] = $diff;
                    $updates[$k]['ID'] = $goods[$k]['ID'];
                }
            }
        }
        parent::save($updates);
    }

    protected function add($element)
    {
        $element['prev_structure'] = $this->prevGood;
        $element['measure'] = '';

        parent::add($element);
    }

    protected function getCategories($goodId)
    {
        $db = Db::getInstance();

        if (!array_key_exists($goodId, $this->goodToCat)) {
            $sql = "SELECT DISTINCT category_id FROM {$this->structureMedium} ".
                "WHERE good_id = {$goodId}";
            $categories = $db->select($sql);

            $this->goodToCat[$goodId] = array();
            foreach ($categories as $item) {
                $this->goodToCat[$goodId][] = $item['category_id'];
            }
        }

        return $this->goodToCat[$goodId];
    }
}
