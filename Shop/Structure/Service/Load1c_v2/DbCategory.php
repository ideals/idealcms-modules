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

    protected $structureSchema = array();

    protected $prevCat = '1-8';

    public function __construct()
    {
        $config = Config::getInstance();
        $prefix = $config->db['prefix'];
        $this->structureCat = $prefix . $this->structureCat;
        $this->category = $prefix . $this->category;
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
        $where = array(
            'key' => 'not-1c',
        );
        $db->update($this->structureCat)
            ->set($values)
            ->where('id_1c <> :key', $where)
            ->exec();

        // Считываем категории из нашей БД
        $where = array(
            'key' => 'not-1c',
        );
        $sql = "SELECT ID, name, cid, lvl, id_1c, is_active, title, count_sale
          FROM `{$this->structureCat}` WHERE id_1c <>:key";

        $result = $db->select($sql, $where);

        return $this->setOldGroups($result);
    }

    public function save($array)
    {
        $this->delete($array['delete']);
        $this->update($array['update']);
        $this->add($array['add']);
    }

    protected function update()
    {

    }

    protected function delete($array)
    {
        foreach ($array as $value) {
            $this->findParent();
        }
        /** проставляем is_active = 0. Удаления как такового нет
         * 2 варианта: категория удалена, категория перемещена
         *
         * Удаление
         *
         * Перемещение
         * 1) Определить предка
         * 2) Определить новый cid предка
         * 3) Определить новый cid элемента на основании 2
         * 4) Подготовить для занесения с новыми cid и поставить is_active = 0
         */
    }

    protected function add($array)
    {
        $db = Db::getInstance();

        foreach ($array as $key => $item) {
            $params = array(
                'id_1c' => $key,
                'cid' => $item['cid'],
                'lvl' => $item['lvl'],
                'name' => $item['name'],
                'url' => Url\Model::translitUrl($item['name']),
                'date_create' => time(),
                'date_mod' => time(),
                'template' => 'index.twig',
                'prev_structure' => $this->prevCat,
                'is_active' => 1
            );
            $db->insert($this->structureCat, $params);
        }
    }

    protected function setOldGroups($groups)
    {
        $oldGroups = array();
        // В качестве ключей для категорий из БД ставим ключ 1С
        foreach ($groups as $v) {
            $v['is_exist'] = false;
            if ($v['title']) {
                $v['is_exist'] = true;
            }

            if ($v['id_1c'] == '') {
                $v['id_1c'] = $v['ID'];
            }
            $oldGroups['id_1c'][$v['id_1c']] = $v;
            $oldGroups['cid'][$v['cid']] = $v;
        }
        return $oldGroups;
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
