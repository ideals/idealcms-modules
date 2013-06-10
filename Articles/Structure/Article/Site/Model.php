<?php
namespace Articles\Structure\Article\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Core\Request;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{
    protected $categoryModel;

    public function detectPageByUrl($url)
    {
        $articleUrl = array_shift($url);

        // TODO для определения названия тэга нужно создавать categoryModel

        if (!$this->params['is_query_param'] AND ($articleUrl == 'tag')) {
            $tag = array_shift($url);
            $request = new Request();
            $request->$articleUrl = $tag;
        }

        if (count($url) > 0) {
            // У статьи не может быть URL с несколькими уровнями вложенности
            return '404';
        }

        $db = Db::getInstance();

        $_sql = "SELECT * FROM {$this->_table} WHERE url='{$articleUrl}' LIMIT 1";

        $list = $db->queryArray($_sql); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($list[0]['ID'])) {
            return '404';
        }
        $list[0]['structure'] = 'Articles_Article';

        $this->path = $list;
        $this->object = end($list);

        $request = new Request();
        $request->action = 'detail';

        return array();
    }


    public function setPath($path)
    {
        parent::setPath($path);
        $this->setCategory();
    }


    public function getCategories()
    {
        $parentUrl = $this->getParentUrl();
        return $this->categoryModel->getCategories($parentUrl);
    }


    public function setCategory($category = '')
    {
        if (!isset($this->categoryModel)) {
            $categoryModel = new \Articles\Structure\Category\Site\Model($this->structurePath);
            $categoryModel->setPath($this->path);
            $this->categoryModel = $categoryModel;
        }
    }


    public function getArticles($page, $onPage)
    {
        $config = Config::getInstance();
        $db = Db::getInstance();

        $start = ($page < 2) ? 0 : ($page - 1) * $onPage;

        $currentCategory = $this->categoryModel->getCurrent();
        if (isset($currentCategory['ID'])) {
            // Вывод статей только определённой категории
            $_sql = "SELECT * FROM i_articles_structure_article WHERE is_active=1
                            AND ID IN (SELECT article_id FROM i_articles_category_article WHERE category_id={$currentCategory['ID']})
                            ORDER BY date_create LIMIT {$start}, {$onPage}";
            $this->path[] = $currentCategory;
            $this->object = $currentCategory;
        } else {
            // Запрос для отображения всех статей
            $_sql = "SELECT * FROM {$this->_table} WHERE is_active = 1 ORDER BY date_create LIMIT {$start}, {$onPage}";
        }
        $list = $db->queryArray($_sql);

        // Проставление правильных URL
        $parentUrl = $this->getParentUrl();
        foreach ($list as $k => $v) {
            $list[$k]['link'] = $parentUrl . '/' . $v['url'] . $config->urlSuffix;
        }

        return $list;
    }


    public function getArticlesCount()
    {
        $db = Db::getInstance();
        $currentCategory = $this->categoryModel->getCurrent();
        if (isset($currentCategory['ID'])) {
            // Считаем статьи только одной категории
            $_sql = "SELECT COUNT(ID) FROM i_articles_structure_article WHERE is_active=1
                            AND ID IN (SELECT article_id FROM i_articles_category_article WHERE category_id={$currentCategory['ID']})";
        } else {
            // Считаем все статьи
            $_sql = "SELECT COUNT(ID) FROM i_articles_structure_article WHERE is_active=1";
        }
        $count = $db->queryArray($_sql);
        return $count[0]['COUNT(ID)'];
    }


    public function getStructureElements()
    {
        return $this->getArticles(0, 9999);
    }


    public function getTemplatesVars()
    {
        if ($this->categoryModel->getCurrent()) {
            return $this->categoryModel->getTemplatesVars();
        } else {
            return parent::getTemplatesVars();
        }
    }

}
