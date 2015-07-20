<?php
namespace Shop\Structure\Service\Load1c_v2\Category;

use Shop\Structure\Service\Load1c_v2\AbstractDb;
use Ideal\Field\Url;
use Ideal\Core\Config;
use Ideal\Field\Cid;
use Ideal\Core\Db;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:31
 */

class DbCategory extends AbstractDb
{
    /** @var string Структура для получения prev_structure */
    protected $structurePart = 'ideal_structure_part';

    /** @var string Предыдущая категория для prev_structure */
    protected $prevCat;

    /**
     *  Установка полей класса - полного имени таблиц с префиксами и получения prev_structure
     */
    public function __construct()
    {
        parent::__construct();
        $db = Db::getInstance();
        $this->table = $this->prefix . 'catalogplus_structure_category';
        $this->structurePart = $this->prefix . $this->structurePart;
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

        $res = $db->select('SELECT ID FROM ' . $this->table . ' WHERE name="Load1c_default"');
        if (count($res) == 0) {
            $config = Config::getInstance();
            $part = $config->getStructureByName('Ideal_Part');

            $cid = $db->select('SELECT max(cid) as cid FROM ' . $this->table);
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
            $db->insert($this->table, $values);
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
        $res = $db->select('SELECT ID, id_1c FROM ' . $this->table);
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
        $db->update($this->table)
            ->set($values)
            ->exec();

        // Считываем категории из нашей БД
        $sql = "SELECT ID, name, cid, lvl, id_1c, is_active, title FROM `{$this->table}` ORDER BY cid";

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

    public function getParentByCid($parentCid)
    {
        $db = Db::getInstance();

        $sql = "SELECT id_1c FROM {$this->table} WHERE cid = '{$parentCid}' LIMIT 1";
        $id = $db->select($sql);

        return $id[0]['id_1c'];
    }

    /**
     * Добавление категории в БД
     *
     * @param array $element данные о добавляемой категории
     */
    protected function add($element)
    {
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

        parent::add($params);
    }

    /**
     * Проверка таблицы категорий на присутствие полей id_1c и count_sale
     * Если их не существует - ALTER TABLE с соответствующими значениями
     */
    protected function checkTable()
    {
        $db = Db::getInstance();

        $params = array(
            array('table' => $this->table),
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
        $sql = "ALTER TABLE {$this->table} ";
        if (empty($res)) {
            $sql .=  " {$idSql}, {$saleSql}";
            $db->query($sql);
        } elseif (count($res) === 1) {
            $sql .= ($res[0]['Field'] == 'id_1c' ? $saleSql : $idSql);
            $db->query($sql);
        }
    }
}
