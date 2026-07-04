<?php

namespace Articles\Structure\Article\Admin;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Request;

class ModelAbstract extends \Ideal\Structure\Roster\Admin\ModelAbstract
{
    protected $categoryPrevStructure;

    private array $categories;

    public function getToolbar(): string
    {
        if (is_null($this->categoryPrevStructure)) {
            return '';
        }

        $db = Db::getInstance();
        $config = Config::getInstance();
        $_table = $config->db['prefix'] . 'ideal_structure_tag';
        $_sql = sprintf("SELECT * FROM %s WHERE prev_structure='%s' AND is_active=1 ORDER BY cid", $_table, $this->categoryPrevStructure);
        $this->categories = $db->select($_sql);

        $request = new Request();
        $currentCategory = $request->toolbar['category'] ?? 0;

        $select = '<select name="toolbar[category]" class="form-control"><option value="">Все статьи</option>';
        foreach ($this->categories as $category) {
            $selected = '';
            if ($category['ID'] == $currentCategory) {
                $selected = 'selected="selected"';
            }

            $select .= '<option ' . $selected . ' value="' . $category['ID'] . '">' . $category['name'] . '</option>';
        }

        return $select . '</select>';
    }


    /**
     * Добавление к where-запросу фильтра по category_id
     * @param string $where Исходная WHERE-часть
     * @return string Модифицированная WHERE-часть, с расширенным запросом, если установлена GET-переменная category
     */
    protected function getWhere($where)
    {
        $db = Db::getInstance();

        $request = new Request();
        if (!isset($request->toolbar['category'])) {
            return parent::getWhere($where);
        }

        $currentCategory = $request->toolbar['category'];

        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'articles_medium_taglist';

        if ($currentCategory != '') {
            // Выборка статей, принадлежащих этой категории
            $where .= ' AND e.ID IN (SELECT part_id FROM ' . $table . ' WHERE tag_id='
                . $db->escape_string($currentCategory) . ')';
        }

        return parent::getWhere($where);
    }
}
