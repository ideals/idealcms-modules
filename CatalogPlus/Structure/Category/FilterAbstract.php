<?php
namespace CatalogPlus\Structure\Category;

use Ideal\Core\Filter;
use Ideal\Field\Cid;
use Ideal\Core\Config;

class FilterAbstract extends Filter
{

    /** @var object Объект модели категории */
    protected $categoryModel = array();

    /** @var bool Отображать в категории товары из подкатегорий */
    protected $showNestedElements = true;

    public function getSql()
    {
        $sql = 'SELECT e.* FROM i_catalogplus_structure_good AS e ';
        if (empty($this->where)) {
            $this->generateWhere();
        }
        if (empty($this->orderBy)) {
            $this->generateOrderBy();
        }
        $sql .= $this->where . $this->orderBy;
        return $sql;
    }

    public function getSqlCount()
    {
        $sql = 'SELECT COUNT(e.ID) FROM i_catalogplus_structure_good AS e ';
        if (empty($this->where)) {
            $this->generateWhere();
        }
        $sql .= $this->where;
        return $sql;
    }

    /**
     * Устанавливает объект модели категории
     *
     * @param $model object Объект модели категории
     */
    public function setCategoryModel($categoryModel)
    {
        $this->categoryModel = $categoryModel;
    }

    /**
     * Устанавливает надабность отображать в категории товары из подкатегорий
     *
     * @param $showNestedElements bool
     */
    public function setShowNestedElements($showNestedElements)
    {
        $this->showNestedElements = $showNestedElements;
    }

    /**
     * Генерирует where часть запроса
     *
     */
    protected function generateWhere()
    {
        $this->where = ' WHERE';
        // Добавление к запросу фильтра по category_id
        if (isset($this->categoryModel)) {
            $category = $this->categoryModel->getPageData();

            // Получения товара для категории, для самой главной выводится все товары
            $prevPath = $this->categoryModel->getPath();
            $prevCategory = ($prevPath[count($prevPath) - 2]['structure'] == 'CatalogPlus_Category') ? true : false;

            if (isset($category['ID']) && ($prevCategory)) {
                // Вывод товара только определённой категории
                $config = Config::getInstance();
                $table = $config->db['prefix'] . 'catalogplus_medium_categorylist';
                $categoryWhere = 'category_id = ' . $category['ID'];
                if ($this->showNestedElements) {
                    $catTable = $config->db['prefix'] . 'catalogplus_structure_category';
                    $params = $this->categoryModel->params;
                    $cidModel = new Cid\Model($params['levels'], $params['digits']);
                    $cid = $cidModel->getCidByLevel($category['cid'], $category['lvl'], false);
                    $categoryWhere = " category_id IN (SELECT ID FROM {$catTable}
                                        WHERE cid LIKE '{$cid}%' AND is_active=1)";
                }
                $this->where .= " e.ID IN (SELECT good_id FROM {$table} WHERE {$categoryWhere})";
            }
        }
    }
}
