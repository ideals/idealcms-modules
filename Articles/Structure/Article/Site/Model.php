<?php
namespace Articles\Structure\Article\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Core\Request;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{

    public function detectPageByUrl($url)
    {
        $articleUrl = $url[0];

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


    public function getTitle()
    {
        if (isset($this->object['title']) AND $this->object['title'] != '') {
            return $this->object['title'];
        } else {
            return $this->object['name'];
        }
    }


    public function getArticles($page, $onPage)
    {
        $config = Config::getInstance();
        $db = Db::getInstance();

        $start = ($page < 2) ? 0 : ($page - 1) * $onPage;

        if ($this->object['structure_path'] === "1") {
            // Запрос для отображения всех статей
            $_sql = "SELECT * FROM {$this->_table} WHERE is_active = 1 ORDER BY date_create LIMIT {$start}, {$onPage}";

        } else {
            // Вывод статей только определённой категории
            $categoryId = $this->object['ID'];
            $_sql = "SELECT * FROM i_articles_structure_article WHERE is_active=1
                            AND ID IN (SELECT article_id FROM i_articles_category_article WHERE category_id={$categoryId})
                            ORDER BY date_create LIMIT {$start}, {$onPage}";
        }
        $list = $db->queryArray($_sql);

        // Проставление правильных URL
        $parentUrl = $this->getParentUrl();
        foreach ($list as $k => $v) {
            $list[$k]['url'] = $parentUrl . '/' . $v['url'] . $config->urlSuffix;
        }

        return $list;
    }


    public function getArticlesCount()
    {
        $db = Db::getInstance();
        if ($this->object['structure_path'] === "1") {
            // Считаем все статьи
            $_sql = "SELECT COUNT(ID) FROM i_articles_structure_article WHERE is_active=1";
        } else {
            // Считаем статьи только одной категории
            $categoryId = $this->object['ID'];
            $_sql = "SELECT COUNT(ID) FROM i_articles_structure_article WHERE is_active=1
                            AND ID IN (SELECT article_id FROM i_articles_category_article WHERE category_id={$categoryId})";
        }
        $count = $db->queryArray($_sql);
        return $count[0]['COUNT(ID)'];
    }

}
