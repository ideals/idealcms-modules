<?php
namespace Shop\Structure\Service\Load1c_v2\Good;

use Shop\Structure\Service\Load1c_v2\AbstractDb;
use Ideal\Field\Url;
use Shop\Structure\Service\Load1c_v2\Category;
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

    /** @var array массив категорий с ID и id_1c */
    protected $categories;

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
        $sql = "SELECT sg.ID, sg.prev_structure, sg.name, sg.id_1c, sg.is_active,
            sg.url, sg.articul, sg.description, sc.id_1c as category_id
            FROM {$this->table} as sg LEFT JOIN {$this->structureCat} as sc ON sg.category_id = sc.ID
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

    /**
     * Сохранение изменений и добавление новых товаров в БД
     *
     * @param array $goods массив товаров для сохранения
     */
    public function save($goods)
    {
        $categoryModel = new Category\DbCategory();
        $this->categories = $categoryModel->getCategories();

        parent::save($goods);
    }

    /**
     * Получение инфомрации о товарах
     *
     * @return array key - id_1c
     */
    public function getGoods()
    {
        $db = Db::getInstance();

        $sql = "SELECT * FROM {$this->table}";
        $res = $db->select($sql);
        $result = array();

        foreach ($res as $item) {
            $result[$item['id_1c']] = $item;
        }

        return $result;
    }

    protected function add($element)
    {
        $element['date_create'] = time();
        $element['date_mod'] = time();
        $element['prev_structure'] = $this->prevGood;
        $element['category_id'] = $this->categories[$element['category_id']];
        unset($element['category']);

        parent::add($element);
    }
}
