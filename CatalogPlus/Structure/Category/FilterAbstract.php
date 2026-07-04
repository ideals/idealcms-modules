<?php

namespace CatalogPlus\Structure\Category;

use Ideal\Structure\User\Model;
use Ideal\Core\Filter;
use Ideal\Field\Cid;
use Ideal\Core\Config;

class FilterAbstract extends Filter
{
    /** @var object Объект модели категории */
    protected $categoryModel = [];

    /** @var bool Отображать в категории товары из подкатегорий */
    protected $showNestedElements = true;

    public function getSql(): string
    {
        $config = Config::getInstance();
        $tableName = $config->db['prefix'] . 'catalogplus_structure_good';
        $sql = 'SELECT e.* FROM ' . $tableName . ' AS e ';
        if (empty($this->where)) {
            $this->generateWhere();
        }

        if (empty($this->orderBy)) {
            $this->generateOrderBy();
        }

        return $sql . ($this->where . $this->orderBy);
    }

    public function getSqlCount(): string
    {
        $config = Config::getInstance();
        $tableName = $config->db['prefix'] . 'catalogplus_structure_good';
        $sql = 'SELECT COUNT(e.ID) FROM ' . $tableName . ' AS e ';
        if (empty($this->where)) {
            $this->generateWhere();
        }

        return $sql . $this->where;
    }

    /**
     * Устанавливает объект модели категории
     *
     * @param $model object Объект модели категории
     */
    public function setCategoryModel($categoryModel): void
    {
        $this->categoryModel = $categoryModel;
    }

    /**
     * Устанавливает надабность отображать в категории товары из подкатегорий
     *
     * @param $showNestedElements bool
     */
    public function setShowNestedElements($showNestedElements): void
    {
        $this->showNestedElements = $showNestedElements;
    }

    /**
     * Генерирует where часть запроса
     *
     */
    protected function generateWhere()
    {
        // Добавление к запросу фильтра по category_id
        if ($this->categoryModel !== null) {
            $category = $this->categoryModel->getPageData();

            // Получения товара для категории, для самой главной выводится все товары
            $prevPath = $this->categoryModel->getPath();
            $prevCategory = $prevPath[count($prevPath) - 2]['structure'] == 'CatalogPlus_Category';

            if (isset($category['ID']) && ($prevCategory)) {
                $this->where = ' WHERE';

                // Для авторизированных в админку пользователей отображать товары в скрытых категориях и скрытые товары
                $user = new Model();
                $checkActive = ' ';
                if (!$user->checkLogin()) {
                    $checkActive = ' AND e.is_active=1';
                }

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
                                        WHERE cid LIKE '{$cid}%'{$checkActive})";
                }

                $this->where .= sprintf(' e.ID IN (SELECT good_id FROM %s WHERE %s)%s', $table, $categoryWhere, $checkActive);
            }
        }
    }
}
