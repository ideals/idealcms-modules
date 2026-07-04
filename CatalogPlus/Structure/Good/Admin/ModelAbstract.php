<?php

namespace CatalogPlus\Structure\Good\Admin;

use CatalogPlus\Structure\Offer\Admin\Model;
use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Request;
use Ideal\Core\Util;

class ModelAbstract extends \Ideal\Structure\Roster\Admin\ModelAbstract
{
    protected $categoryPrevStructure;

    private array $categories;

    public function getToolbar(): string
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        // Поиск всех категорий для составления фильтра
        $_table = $config->db['prefix'] . 'ideal_structure_part';
        $sql = sprintf("SELECT * FROM %s WHERE structure='CatalogPlus_Category' LIMIT 1", $_table);
        $tmp = $db->select($sql);
        if (count($tmp) > 0) {
            // Построение prevStructure
            $categoryPrevStructure = explode('-', $tmp[0]['prev_structure']);
            $categoryPrevStructure = end($categoryPrevStructure) . '-' . $tmp[0]['ID'];
            $this->categoryPrevStructure = $categoryPrevStructure;
        } else {
            Util::addError('CatalogPlus: запрос вернул 0 результатов: ' . $sql);
        }

        $_table = $config->db['prefix'] . 'catalogplus_structure_category';
        $sql = sprintf("SELECT * FROM %s WHERE prev_structure='%s' AND is_active=1 ORDER BY cid", $_table, $this->categoryPrevStructure);
        $this->categories = $db->select($sql);

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

            for ($i = 1; $i < (int) $category['lvl']; $i++) {
                if ($i > 8) {
                    break;
                }

                $category['name'] = '-' . $category['name'];
            }

            $select .= '<option ' . $selected . ' value="' . $category['ID'] . '">' . $category['name'] . '</option>';
        }

        return $select . '</select>';
    }

    public function delete(): void
    {
        parent::delete();

        [, $ps] = explode('-', $this->pageData['prev_structure'], 2);
        $offerModel = new Model($ps . '-' . $this->pageData['ID']);
        $offerModel->deleteByGood();
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
}
