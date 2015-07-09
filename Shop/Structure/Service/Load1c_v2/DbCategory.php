<?php
namespace Shop\Structure\Service\Load1c_v2;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Field\Url;

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

    protected $structurePart = 'ideal_structure_part';

    protected $structureSchema = array();

    protected $prevCat;

    public function __construct()
    {
        $config = Config::getInstance();
        $db = Db::getInstance();
        $prefix = $config->db['prefix'];
        $this->structureCat = $prefix . $this->structureCat;
        $this->structurePart = $prefix . $this->structurePart;
        $this->prevCat = $db->exec(
            'SELECT prev_structure FROM ' . $this->structurePart . ' WHERE structure = "CatalogPlus_Category"'
        );
    }

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
        $where = array(
            'key' => 'not-1c',
        );
        $sql = "SELECT ID, name, cid, lvl, id_1c, is_active, title, count_sale
          FROM `{$this->structureCat}` ORDER BY cid";

        $tmp = $db->select($sql, $where);

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

    public function save($array)
    {
        foreach ($array as $element) {
            if (isset($element['ID'])) {
                $this->update($element);
            } else {
                $this->add($element);
            }
        }
    }

    protected function update($array)
    {
        $db = Db::getInstance();

        $id = array('id' => $array['ID']);
        unset($array['ID']);
        $db->update($this->structureCat)->set($array)->where('ID=:id', $id)->exec();
    }

    protected function add($array)
    {
        $db = Db::getInstance();

        $params = array(
            'url' => Url\Model::translitUrl($array['name']),
            'date_create' => time(),
            'date_mod' => time(),
            'template' => 'Ideal_Page',
            'prev_structure' => $this->prevCat,
        );
        foreach ($array as $key => $item) {
            $params[$key] = $item;
        }

        $db->insert($this->structureCat, $params);
    }

    protected function checkTable()
    {
        $db = Db::getInstance();

        $params = array(
            array('table' => $this->structureCat),
        );
        $sql = 'SHOW COLUMNS FROM &table';
        $res = $db->select($sql, null, $params[0]);
        foreach ($res as $key => $value) {
            $this->structureSchema[$value['Field']] = $value;
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
