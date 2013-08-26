<?php
namespace Articles\Structure\Article\Admin;

use Ideal\Core\Db;
use Ideal\Core\Request;

class Model extends \Ideal\Structure\Roster\Admin\ModelAbstract
{
    public function getToolbar()
    {
        $db = Db::getInstance();
        $_table = 'i_articles_structure_category';
        $structurePath = '11-1';
        $_sql = "SELECT * FROM {$_table} WHERE structure_path='{$structurePath}' AND is_active=1 ORDER BY cid";
        $this->categories = $db->queryArray($_sql);

        $request = new Request();
        $currentCategory = $request->toolbar['category'];

        $select = '<select name="toolbar[category]"><option value="">Все статьи</option>';
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
        $currentCategory = $request->category;

        if ($currentCategory != '') {
            if ($where != '') {
                $where .= ' AND ';
            }
            // Выборка статей, принадлежащих этой категории
            $where .= 'ID IN (SELECT article_id FROM i_articles_category_article WHERE category_id='
                . mysql_real_escape_string($currentCategory) . ')';
        }
        if($where != ''){
            $where = 'WHERE '.$where;
        }

        return $where;
    }
}
