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
        // Поиск всех категорий для составления фильтра
        $_table = $config->db['prefix'] . 'ideal_structure_part';
        $_sql = "SELECT * FROM {$_table} WHERE structure='CatalogPlus_Category' LIMIT 1";
        $tmp = $db->select($_sql);
        if (count($tmp) > 0) {
            // Построение prevStructure
            $categoryPrevStructure = explode('-', $tmp[0]['prev_structure']);
            $categoryPrevStructure = end($categoryPrevStructure) . '-' . $tmp[0]['ID'];
            $this->categoryPrevStructure = $categoryPrevStructure;
        } else {
            \FB::error($this, 'CatalogPlus');
        }
        $_table = $config->db['prefix'] . 'catalogplus_structure_category';
        $_sql = "SELECT * FROM {$_table} WHERE prev_structure='{$this->categoryPrevStructure}' AND is_active=1 ORDER BY cid";
        $this->categories = $db->select($_sql);

        $request = new Request();
        $currentCategory = '';
        if (isset($request->toolbar['category'])) {
            $currentCategory = $request->toolbar['category'];
        }

        $select = '<select class="form-control" name="toolbar[category]"><option value="">Не фильтровать</option>';
        foreach ($this->categories as $category) {
            $selected = '';
            if ($category['ID'] == $currentCategory) {
                $selected = 'selected="selected"';
            }
            for ($i = 1; $i < (int)$category['lvl']; $i++) {
                if ($i > 8) {
                    break;
                }
                $category['name'] = '-' . $category['name'];
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
        $currentCategory = '';
        if (isset($request->toolbar['category'])) {
            $currentCategory = $request->toolbar['category'];
        }
        if ($currentCategory != '') {
            $db = DB::getInstance();
            $config = Config::getInstance();
            $table = $config->db['prefix'] . 'catalogplus_medium_categorylist';
            if ($where != '') {
                $where .= ' AND ';
            }
            // Выборка статей, принадлежащих этой категории
            $where .= 'ID IN (SELECT good_id FROM ' . $table . ' WHERE category_id='
                . $db->real_escape_string($currentCategory) . ')';
        }
        if ($where != '') {
            $where = 'WHERE ' . $where;
        }

        return $where;
    }

    public function delete()
    {
        $result = parent::delete();
        if ($result) {
            [, $ps] = explode('-', $this->pageData['prev_structure'], 2);
            $offerModel = new \CatalogPlus\Structure\Offer\Admin\Model($ps . '-' . $this->pageData['ID']);
            $offerModel->deleteByGood();
        }
    }
}
