<?php
namespace Articles\Structure\Article\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Core\Request;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{
    /**
     * @var $categoryModel \Articles\Structure\Category\Site\Model
     */
    protected $categoryModel;
    protected $currentCategory;

    public function detectPageByUrl($url, $path)
    {
        // Определяем, нет ли в URL категории
        $this->categoryModel = new \Articles\Structure\Category\Site\Model($this->structurePath);
        $url = $this->categoryModel->detectPageByUrl($url, $path);
        if (count($url) == 0) {
            // Прошло успешно определение страницы категории, значит статью определять не надо
            $this->path = $path;
            return array();
        }

        $articleUrl = array_shift($url);

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

        $this->path = array_merge($path, $list);
        $this->object = end($list);

        $request = new Request();
        $request->action = 'detail';

        return array();
    }


    public function detectCurrentCategory($path)
    {
        if (!isset($this->categoryModel)) {
            // Если категория не была определена на этапе DetectPageByUrl, то нужно
            // проверить, нет ли категории в query_string
            $this->categoryModel = new \Articles\Structure\Category\Site\Model($this->structurePath);
            $this->categoryModel->detectPageByUrl(array(), $path);
        }

        $this->currentCategory = $this->categoryModel->getCurrent();
        if ($this->currentCategory) {
            $this->object = $this->currentCategory;
        }
    }


    public function getCategories()
    {
        $parentUrl = $this->getParentUrl();
        return $this->categoryModel->getCategories($parentUrl);
    }


    public function getArticles($page, $onPage)
    {
        $config = Config::getInstance();
        $db = Db::getInstance();

        $start = ($page < 2) ? 0 : ($page - 1) * $onPage;

        if ($this->currentCategory) {
            // Вывод статей только определённой категории
            $_sql = "SELECT * FROM i_articles_structure_article WHERE is_active=1
                            AND ID IN (SELECT article_id FROM i_articles_category_article
                                              WHERE category_id={$this->currentCategory['ID']})
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
        $this->categoryModel = new \Articles\Structure\Category\Site\Model($this->structurePath);
        $this->categoryModel->setPath($this->path);
        $articles = $this->getArticles(0, 9999);
        $categories = $this->getCategories();
        return array_merge($categories, $articles);
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
