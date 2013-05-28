<?php
namespace Articles\Structure\Category\Site;

use Ideal\Core\Db;
use Ideal\Field;
use Ideal\Core\Config;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{

    public function getArticlesCount()
    {
        $db = Db::getInstance();
        if ($this->object['structure_path'] === "1") {
            // Считаем все статьи
            $_sql = "SELECT COUNT(ID) FROM i_articles_structure_paper WHERE is_active=1";
        } else {
            // Считаем статьи только одной категории
            $categoryId = $this->object['ID'];
            $_sql = "SELECT COUNT(ID) FROM i_articles_structure_paper WHERE is_active=1
                            AND ID IN (SELECT paper FROM i_articles_paper WHERE articles={$categoryId})";
        }
        $count = $db->queryArray($_sql);
        return $count[0]['COUNT(ID)'];
    }


    public function getArticles($page, $onPage)
    {
        $config = Config::getInstance();
        $db = Db::getInstance();

        $start = ($page < 2) ? 0 : ($page - 1) * $onPage;

        if ($this->object['structure_path'] === "1") {
            // Запрос для отображения всех статей
            $_sql = "SELECT * FROM i_articles_structure_paper WHERE is_active = 1 ORDER BY date_create LIMIT {$start}, {$onPage}";

        } else {
            // Вывод статей только определённой категории
            $categoryId = $this->object['ID'];
            $_sql = "SELECT * FROM i_articles_structure_paper WHERE is_active=1
                            AND ID IN (SELECT paper FROM i_articles_paper WHERE articles={$categoryId})
                            ORDER BY date_create LIMIT {$start}, {$onPage}";
        }
        $list = $db->queryArray($_sql);

        // Проставление правильных URL
        $parentUrl = $this->getParentUrl();
        foreach ($list as $k => $v) {
            $news[$k]['url'] = $parentUrl . '/' . $v['url'] . $config->urlSuffix;
            $news[$k]['date_create'] = \Ideal\Core\Util::dateReach($v['date_create']);
        }

        return $list;
    }

    public function getCategories()
    {
        $db = Db::getInstance();
        $_sql = "SELECT * FROM i_articles_structure_category WHERE structure_path='{$this->structurePath}' AND is_active=1";
        //return $db->queryArray($_sql);
        $list = $db->queryArray($_sql);
        $url = new \Ideal\Field\Url\Model();

        $first = array(
            'cap'   => 'Все статьи',
            'url'   => '/articles.html',
            'class' => ''
        );

        $path = $this->getPath();
        $end = count($path) - 1; // последний элемент пути
        if ($path[$end - 1]['structure'] == $path[$end]['structure']) {
            // Если последний и предпоследний - категории, убираем последний элемент,
            // т.к. вложенных категорий у нас нет
            $last = array_pop($path);
            $lastID = $last['ID'];
        } else {
            $lastID = 0;
            $first['class'] = 'active';
        }

        $url->setParentUrl($path);
        foreach ($list as $k => $v) {
            $list[$k]['url'] = $url->getUrl($v);
            $list[$k]['class'] = ($v['ID'] == $lastID) ? 'active' : '';
        }

        array_unshift($list, $first);

        return $list;
    }

}