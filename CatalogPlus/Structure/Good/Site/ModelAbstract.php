<?php
namespace CatalogPlus\Structure\Good\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Core\Util;
use Ideal\Field\Cid;

class ModelAbstract extends \Ideal\Core\Site\Model
{
    /**
     * @var $categoryModel \CatalogPlus\Structure\Category\Site\Model
     */
    protected $categoryModel;
    protected $currentCategory;
    /** @var bool Отображать в категории товары из подкатегорий */
    protected $showNestedElements = true;

    public function detectPageByUrl($path, $url)
    {
        // Определяем, нет ли в URL категории
        $this->categoryModel = new \CatalogPlus\Structure\Category\Site\Model($this->prevStructure);
        $model = $this->categoryModel->detectPageByUrl($path, $url);
        if (!$model->is404) {
            // Прошло успешно определение страницы категории, значит товар определять не надо
            return $model;
        }

        if (count($url) > 1) {
            // У товара не может быть URL с несколькими уровнями вложенности
            $this->is404 = true;
            $this->path = $path;
            return $this;
        }

        $url = array_shift($url);

        // Ищем товар по URL в базе
        $db = Db::getInstance();
        $_sql = "SELECT * FROM {$this->_table} WHERE url='{$url}' LIMIT 1";
        $list = $db->queryArray($_sql);

        // Товар не нашли, возвращаем 404
        if (!isset($list[0]['ID'])) {
            $this->is404 = true;
            return $this;
        }

        // Товар найден, проводим необходимую инициализацию свойств

        $list[0]['structure'] = 'CatalogPlus_Good';

        $this->path = array_merge($path, $list);
        $this->pageData = end($list);

        return $this;
    }

    public function getCategories()
    {
        $parentUrl = $this->getParentUrl();
        return $this->categoryModel->getCategories($parentUrl);
    }

    public function getStructureElements()
    {
        $this->categoryModel = new \CatalogPlus\Structure\Category\Site\Model($this->prevStructure);
        $this->categoryModel->setPath($this->path);
        $this->params['elements_site'] = 9999;
        $articles = $this->getList(1);
        $categories = $this->getCategories();
        return array_merge($categories, $articles);
    }

    public function getData()
    {
        $data = $this->pageData['data'];
        $configTemplate = new \CatalogPlus\Template\Data\Model('');
        if ($data['ID']) unset($data['ID']);
        if ($data['prev_structure']) unset($data['prev_structure']);
        foreach ($data as $k => $v) {
            unset($data[$k]);
            if ((is_string($v) && strlen($v) < 1) || (is_null($v))) continue;
            $data[$k]['name'] = $configTemplate->fields[$k]['label'];
            $data[$k]['value'] = $v;
        }
        return $data;
    }

    /**
     * @param int $page Номер отображаемой страницы
     * @return array Полученный список элементов
     */
    public function getList($page = null)
    {
        $list = parent::getList($page);

        // Построение правильных URL
        $url = new \Ideal\Field\Url\Model();
        $url->setParentUrl($this->path);
        if (is_array($list) and count($list) != 0 ) {
            foreach ($list as $k => $v) {
                $list[$k]['link'] = $url->getUrl($v);
            }
        }

        return $list;
    }

    /**
     * Добавление к where-запросу фильтра по category_id
     * @param string $where Исходная WHERE-часть
     * @return string Модифицированная WHERE-часть, с расширенным запросом, если установлена GET-переменная category
     */
    protected function getWhere($where)
    {
        if (isset($this->categoryModel)) {
            $category = $this->categoryModel->getPageData();
            if (isset($category['ID'])) {
                // Вывод товара только определённой категории
                $config = Config::getInstance();
                $table = $config->db['prefix'] . 'catalogplus_medium_categorylist';
                $categoryWhere = 'category_id = ' . $category['ID'];
                if ($this->showNestedElements) {
                    $catTable = $config->db['prefix'] . 'catalogplus_structure_category';
                    $params = $this->categoryModel->params;
                    $cidModel = new Cid\Model($params['levels'], $params['digits']);
                    $cid = $cidModel->getCidByLevel($category['cid'], $category['lvl'], false);
                    $categoryWhere = " category_id IN (SELECT ID FROM {$catTable} WHERE cid LIKE '{$cid}%' AND is_active=1)";
                }
                $where .= " AND e.ID IN (SELECT good_id FROM {$table}
                                                  WHERE {$categoryWhere})";
            }
        }

        $where = parent::getWhere($where . ' AND e.is_active=1');

        return $where;
    }

    public function detectPath()
    {
        if (isset($this->categoryModel)) {
            // Установлена категория, значит определять товар не нужно, определяем предков структуры товара
            $path = parent::detectPath();
            // todo определить вложенные категории
            $catPath = $this->categoryModel->getLocalPath();
            $this->path = array_merge($path, $catPath);
            return $this->path;
        }

        // Определение пути из товара
        $config = Config::getInstance();
        $db = Db::getInstance();

        $good = $this->pageData;
        list($parentStructure, $parentId) = explode('-', $good['prev_structure']);
        if ($parentStructure == 0) {
            // Частный случай - определение пути не из конкретного товара, а из уровня выше
            return parent::detectPath();
        }
        $structure = $config->getStructureById($parentStructure);

        // Находим предка — структуру статей
        $parentClassName = Util::getClassName($structure['structure'], 'Structure') . '\\Site\\Model';
        $parentModel = new $parentClassName($good['structure']);
        $parentModel->setPageDataById($parentId);

        $path = $parentModel->detectPath();

        $this->path = $path;
        $this->path[] = $good;

        return $this->path;
    }

    protected function getLocalPath()
    {
        return array();
    }

    public function getHeader()
    {
        $header = '';
        if (isset($this->pageData['content'])) {
            // Если есть шаблон с контентом, пытаемся из него извлечь заголовок H1
            list($header, $text) = $this->extractHeader($this->pageData['content']);
            $this->pageData['content'] = $text;
        }

        if ($header == '') {
            // Если заголовка H1 в тексте нет, берём его из названия name
            $header = $this->pageData['name'];
        }
        return $header;
    }

    public function setCategoryModel($model)
    {
        $this->categoryModel = $model;
    }

    /**
     * Установка свойств объекта по данным из массива $model
     *
     * Вызывается при копировании данных из одной модели в другую
     * @param Model $model Массив переменных объекта
     * @return $this Либо ссылка на самого себя, либо новый объект модели
     */
    public function setVars($model)
    {
        $category = new \CatalogPlus\Structure\Category\Site\Model('');
        $path = $model->getPath();
        $end = array_pop($path);
        $end['structure'] = 'CatalogPlus_Category';
        $path[] = $end;
        $model->setPath($path);
        $category->setVars($model);
        //$category->setGoods($this);
        return $category;
    }

}
