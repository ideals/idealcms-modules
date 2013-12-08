<?php
namespace Shop\Structure\Good\Admin;

use Ideal\Core\Request;
use Shop\Structure\CategoryMulti\Getters;

class ModelAbstract extends \Ideal\Structure\Roster\Admin\ModelAbstract
{
    public $category;

    public function setPath($path)
    {
        parent::setPath($path);

        // Находим prev_structure для связанных категорий товара
        $this->category = new \Shop\Structure\CategoryMulti\Admin\Model('');
        $this->category->detectPrevStructure($this->path);
    }

    public function getToolbar()
    {
        $getter = new Getters\CategoryList($this, '');
        $categories = $getter->getList();

        $request = new Request();
        $toolBar = $request->toolbar;
        $currentCategory = (isset($toolBar['category'])) ? $toolBar['category'] : '';

        $select = '<select name="toolbar[category]"><option value="">Все категории</option>';
        foreach ($categories as $id => $category) {
            $selected = '';
            if ($id == $currentCategory) {
                $selected = 'selected="selected"';
            }
            $select .= '<option ' . $selected . ' value="' . $id . '">' . $category . '</option>';
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
        $getter = new Getters\CategoryList($this, '');

        $request = new Request();
        $toolBar = $request->toolbar;
        $currentCategory = (isset($toolBar['category'])) ? $toolBar['category'] : '';

        if ($currentCategory != '') {
            if ($where != '') {
                $where .= ' AND ';
            }
            // Выборка статей, принадлежащих этой категории
            $where .= $getter->getWhereFilter($currentCategory);
        }

        if ($where != '') {
            $where = 'WHERE ' . $where;
        }

        return $where;
    }
}
