<?php
namespace CatalogPlus\Structure\Good\Admin;

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
        // Поиск всех категорий для состовления фильтра
        $_table = $config->db['prefix'] . 'ideal_structure_datalist';
        $where = $this->pageData['url'];
        $_sql = "SELECT * FROM {$_table} WHERE parent_url='{$where}' LIMIT 1";
        $tmp = $db->queryArray($_sql);
        if(count($tmp) > 0){
            // Построение prevStructure
            $tmp = $tmp[0];
            $this->categoryPrevStructure = explode('-', $tmp['prev_structure']);
            $this->categoryPrevStructure = end($this->categoryPrevStructure) . '-' . $tmp['ID'];
        } else{
            \FB::error($this, 'CatalogPlus');
        }
        $_table = $config->db['prefix'] . 'catalogplus_structure_category';
        $_sql = "SELECT * FROM {$_table} WHERE prev_structure='{$this->categoryPrevStructure}' AND is_active=1 ORDER BY cid";
        $this->categories = $db->queryArray($_sql);

        $request = new Request();
        $currentCategory = $request->toolbar['category'];

        $select = '<select class="form-control" name="toolbar[category]"><option value="">Не фильтровать</option>';
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
        $table = $config->db['prefix'] . 'catalogplus_medium_categorylist';

        if ($currentCategory != '') {
            if ($where != '') {
                $where .= ' AND ';
            }
            // Выборка статей, принадлежащих этой категории
            $where .= 'ID IN (SELECT good_id FROM ' . $table . ' WHERE category_id='
                . mysql_real_escape_string($currentCategory) . ')';
        }
        if($where != ''){
            $where = 'WHERE '.$where;
        }

        return $where;
    }
}
