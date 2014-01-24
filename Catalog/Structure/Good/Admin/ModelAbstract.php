<?php
namespace Catalog\Structure\Good\Admin;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Core\Request;

class ModelAbstract extends \Ideal\Structure\Roster\Admin\ModelAbstract
{
    public function getToolbar()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $_table = $config->db['prefix'] . 'catalog_structure_category';
        $_sql = "SELECT * FROM {$_table}
                 WHERE prev_structure = (
                     SELECT prev_structure FROM {$_table} WHERE ID = (
                        SELECT category_id FROM {$this->_table} WHERE prev_structure='{$this->prevStructure}' LIMIT 1
                     ) LIMIT 1
                 ) AND is_active=1 ORDER BY name";
        $this->categories = $db->queryArray($_sql);

        $request = new Request();
        $toolBar = $request->toolbar;
        $currentCategory = (isset($toolBar['category'])) ? $toolBar['category'] : '';

        $select = '<select name="toolbar[category]"><option value="">Все категории</option>';
        foreach ($this->categories as $category) {
            $selected = '';
            if ($category['ID'] == $currentCategory) {
                $selected = 'selected="selected"';
            }
            $select .= '<option ' . $selected . ' value="' . $category['ID'] . '">' . $category['name'] . '</option>';
        }
        $select .= '</select>';

        return $select;
    }


    /**
     * Добавление к where-запросу фильтра по category_id
     * @param string $where Исходная WHERE-часть
     * @return string Модифицированная WHERE-часть, с расширенным запросом, если установлена GET-переменная category
     */
    protected function getWhere($where)
    {
        $request = new Request();
        $toolBar = $request->toolbar;
        $currentCategory = (isset($toolBar['category'])) ? $toolBar['category'] : '';

        if ($currentCategory != '') {
            if ($where != '') {
                $where .= ' AND ';
            }
            // Выборка статей, принадлежащих этой категории
            $where .= 'category_id=' . mysql_real_escape_string($currentCategory);
        }

        if ($where != '') {
            $where = 'WHERE ' . $where;
        }

        return $where;
    }
}
