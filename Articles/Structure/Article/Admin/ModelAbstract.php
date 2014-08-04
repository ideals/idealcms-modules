<?php
namespace Articles\Structure\Article\Admin;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Request;

class ModelAbstract extends \Ideal\Structure\Roster\Admin\ModelAbstract
{
    protected $categoryPrevStructure;

    public function getToolbar()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $_table = $config->db['prefix'] . 'articles_structure_category';
        $_sql = "SELECT * FROM {$_table} WHERE prev_structure='{$this->categoryPrevStructure}' AND is_active=1 ORDER BY cid";
        $this->categories = $db->select($_sql);

        $request = new Request();
        $currentCategory = $request->toolbar['category'];

        $select = '<select name="toolbar[category]" class="form-control"><option value="">Все статьи</option>';
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
        $currentCategory = $request->toolbar['category'];

        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'articles_medium_categorylist';

        if ($currentCategory != '') {
            // Выборка статей, принадлежащих этой категории
            $where .= ' AND e.ID IN (SELECT article_id FROM ' . $table . ' WHERE category_id='
                . mysql_real_escape_string($currentCategory) . ')';
        }

        $where = parent::getWhere($where);

        return $where;
    }
}
