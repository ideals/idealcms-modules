<?php
namespace Shop\Structure\Good\Admin;

use Ideal\Core\Db;
use Ideal\Core\Request;

class ModelAbstract extends \Ideal\Structure\Roster\Admin\ModelAbstract
{
    /**
     * @var string Путь к категориям связанным с этим товаром
     */
    protected $categoryStructurePath = '1-22';

    public function getToolbar()
    {
        $db = Db::getInstance();
        $_table = 'i_shop_structure_category';
        $_sql = "SELECT * FROM {$_table}
                 WHERE structure_path='{$this->categoryStructurePath}' AND is_active=1 ORDER BY cid";
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
     * @param $where Исходная WHERE-часть
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
            $where .= 'ID IN (SELECT good_id FROM i_shop_category_good WHERE category_id='
                . mysql_real_escape_string($currentCategory) . ')';
        }

        if ($where != '') {
            $where = 'WHERE ' . $where;
        }

        return $where;
    }
}
