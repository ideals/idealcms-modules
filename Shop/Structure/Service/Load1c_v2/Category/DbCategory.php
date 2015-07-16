<?php
namespace Shop\Structure\Service\Load1c_v2\Category;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Field\Url;
use Ideal\Field\Cid;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:31
 */

class DbCategory
{
    /** @var string префикс таблиц */
    protected $prefix;

    /** @var string Структуры категорий */
    protected $structureCat = 'catalogplus_structure_category';

    /** @var string Структура для получения prev_structure */
    protected $structurePart = 'ideal_structure_part';

    /** @var string Предыдущая категория для prev_structure */
    protected $prevCat;

    /**
     *  Установка полей класса - полного имени таблиц с префиксами и получения prev_structure
     */
    public function __construct()
    {
        $config = Config::getInstance();
        $db = Db::getInstance();
        $prefix = $config->db['prefix'];
        $this->structureCat = $prefix . $this->structureCat;
        $this->structurePart = $prefix . $this->structurePart;
        $res = $db->select(
            'SELECT ID FROM ' . $this->structurePart . ' WHERE structure = "CatalogPlus_Category" LIMIT 1'
        );
        $this->prevCat = '0-' . $res[0]['ID'];
    }


    /**
     * Если необходимо - создание категории товаров из выгрузки, у которых не была указана категория
     */
    public function createDefaultCategory()
    {
        $db = Db::getInstance();

        $res = $db->select('SELECT ID FROM ' . $this->structureCat . ' WHERE name="Load1c_default"');
        if (count($res) == 0) {
            $config = Config::getInstance();
            $part = $config->getStructureByName('Ideal_Part');

            $cid = $db->select('SELECT max(cid) as cid FROM ' . $this->structureCat);
            $cidModel = new Cid\Model($part['params']['levels'], $part['params']['digits']);
            $cid = $cidModel->getBlock($cid[0]['cid'], 1, '+1');
            $values = array(
                'lvl' => '1',
                'id_1c' => 'Load1c_default',
                'prev_structure' => $this->prevCat,
                'cid' => $cidModel->reconstruct($cid),
                'structure' => 'CatalogPlus_Category',
                'name' => 'Load1c_default',
                'url' => 'Load1c_default',
                'date_create' => time(),
                'date_mod' => time(),
                'is_active' => '0'
            );
            $db->insert($this->structureCat, $values);
        }
    }

    /**
     * Получение списка категорий для распределения товаров из выгрузки
     *
     * @return array массив категорий ключ - id_1c, значение - ID категории в базе
     */
    public function getCategories()
    {
        $db = Db::getInstance();

        $categories = array();
        $res = $db->select('SELECT ID, id_1c FROM ' . $this->structureCat);
        foreach ($res as $category) {
            $categories[$category['id_1c']] = $category['ID'];
        }

        return $categories;
    }

    /**
     * Получение массива категорий из базы данных
     *
     * @return array ключ - ид_1с, значение - массив полей в таблице о категории.
     */
    public function parse()
    {
        $db = Db::getInstance();
        $this->checkTable();

        // Сбрасываем счетчик товаров для групп
        $values = array(
            'num' => 0,
            'count_sale' => 0,
            'is_not_menu' => 0,
        );
        $db->update($this->structureCat)
            ->set($values)
            ->exec();

        // Считываем категории из нашей БД
        $sql = "SELECT ID, name, cid, lvl, id_1c, is_active, title FROM `{$this->structureCat}` ORDER BY cid";

        $tmp = $db->select($sql);

        $result = array();
        // Если категория не 1с ключ в массиве - порядковый номер, иначе - id_1c
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
     * Сохранение полученных из XML изменений
     *
     * @param array $elements массив категорий для записи в базу данных
     */
    public function save($elements)
    {
        foreach ($elements as $element) {
            // Если присутствует ID - категория уже была в БД и её надо только обновить
            if (isset($element['ID'])) {
                $this->update($element);
            } else {
                $this->add($element);
            }
        }
    }

    /**
     * Обновление категории в БД
     *
     * @param array $element массив данных об обновляемой категории
     */
    protected function update($element)
    {
        $db = Db::getInstance();

        $id = array('id' => $element['ID']);
        $element['date_mod'] = time();
        unset($element['ID']);
        $db->update($this->structureCat)->set($element)->where('ID=:id', $id)->exec();
    }

    /**
     * Добавление категории в БД
     *
     * @param array $element данные о добавляемой категории
     */
    protected function add($element)
    {
        $db = Db::getInstance();

        $params = array(
            'url' => Url\Model::translitUrl($element['name']),
            'date_create' => time(),
            'date_mod' => time(),
            'template' => 'Ideal_Page',
            'prev_structure' => $this->prevCat,
        );
        foreach ($element as $key => $item) {
            $params[$key] = $item;
        }

        $db->insert($this->structureCat, $params);
    }

    /**
     * Проверка таблицы категорий на присутствие полей id_1c и count_sale
     * Если их не существует - ALTER TABLE с соответствующими значениями
     */
    protected function checkTable()
    {
        $db = Db::getInstance();

        $params = array(
            array('table' => $this->structureCat),
        );
        $sql = 'SHOW COLUMNS FROM &table';
        $res = $db->select($sql, null, $params[0]);
        foreach ($res as $key => $value) {
            if ($value['Field'] != 'id_1c' && $value['Field'] != 'count_sale') {
                unset ($res[$key]);
            }
        }
        $res = array_values($res);
        $idSql = "ADD COLUMN `id_1c` varchar(75) DEFAULT 'not-1c' AFTER ID";
        $saleSql = "ADD COLUMN `count_sale` int(11) DEFAULT 0 AFTER `description`";
        $sql = "ALTER TABLE {$this->structureCat} ";
        if (empty($res)) {
            $sql .=  " {$idSql}, {$saleSql}";
            $db->query($sql);
        } elseif (count($res) === 1) {
            $sql .= ($res[0]['Field'] == 'id_1c' ? $saleSql : $idSql);
            $db->query($sql);
        }
    }
}
