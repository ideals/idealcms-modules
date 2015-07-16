<?php
namespace Shop\Structure\Service\Load1c_v2\Good;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Field\Url;
use Shop\Structure\Service\Load1c_v2\Category;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:31
 */

class DbGood
{
    /** @var string префикс таблиц */
    protected $prefix;

    /** @var string Структуры категорий */
    protected $structureGood = 'catalogplus_structure_good';

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
        $config = Config::getInstance();
        $db = Db::getInstance();
        $prefix = $config->db['prefix'];
        $this->structureGood = $prefix . $this->structureGood;
        $this->structurePart = $prefix . $this->structurePart;
        $this->structureCat = $prefix . $this->structureCat;
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
            FROM {$this->structureGood} as sg LEFT JOIN {$this->structureCat} as sc ON sg.category_id = sc.ID
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
        foreach ($goods as $element) {
            // Если присутствует ID - товар уже был в БД и его надо только обновить
            if (isset($element['ID'])) {
                $this->update($element);
            } else {
                $this->add($element);
            }
        }
    }

    /**
     * Обновление данных о уже существующем товаре
     *
     * @param array $element данные для обновление, помимо ID товара
     */
    private function update($element)
    {
        $db = Db::getInstance();

        $db->update($this->structureGood)->set($element)->where('ID = :ID', $element)->exec();
    }

    /**
     * Добавление нового товара в БД
     *
     * @param array $element данные для добавления в БД
     */
    private function add($element)
    {
        $db = Db::getInstance();

        $element['date_create'] = time();
        $element['date_mod'] = time();
        $element['prev_structure'] = $this->prevGood;
        $element['category_id'] = $this->categories[$element['category_id']];
        unset($element['category']);

        $db->insert($this->structureGood, $element);
    }
}
