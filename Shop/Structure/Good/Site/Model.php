<?php
namespace Shop\Structure\Good\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Field\Url;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{

    public function detectPageByUrl($url, $path)
    {
        $config = Config::getInstance();
        // Определяем, нет ли в URL категории
        $this->categoryModel = new \Shop\Structure\Category\Site\Model($this->structurePath);
        $url = $this->categoryModel->detectPageByUrl($url, $path);
        if (count($url) == 0) {
            // Прошло успешно определение страницы категории, значит статью определять не надо
            $this->path = $this->categoryModel->getPath();
            return array();
        }

        $articleUrl = array_shift($url);

        if (count($url) > 0) {
            // У статьи не может быть URL с несколькими уровнями вложенности
            return '404';
        }
        $page = explode('/', $_SERVER['REDIRECT_URL']);
        $page = end($page);
        $tmp = $articleUrl . $config->urlSuffix;
        if ($page != $tmp) {
            $page = $_SERVER['REDIRECT_URL']. $config->urlSuffix;
            header ('HTTP/1.1 301 Moved Permanently');
            header ('Location: '.$page);
            exit();
            //return '404';
        }

        $db = Db::getInstance();

        $_sql = "SELECT * FROM {$this->_table} WHERE url='{$articleUrl}' LIMIT 1";

        $list = $db->queryArray($_sql); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($list[0]['ID'])) {
            return '404';
        }
        $list[0]['structure'] = 'Shop_Good';

        $this->path = array_merge($path, $list);
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


    public function getStructureElements()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $urlModel = new Url\Model();

        $_sql = "SELECT * FROM {$this->_table} WHERE is_active=1 ORDER BY cid";
        $list = $db->queryArray($_sql);

        $lvl = 0;
        $url = array('0' => array('url' => $config->structures[0]['url']));
        foreach ($list as $k => $v) {
            if ($v['lvl'] > $lvl) {
                if ($v['url'] != '/') {
                    $url[] = $list[$k - 1];
                }
                $urlModel->setParentUrl($url);
            } elseif ($v['lvl'] < $lvl) {
                // Если двойной или тройной выход добавляем соответствующий мультипликатор
                $c = $lvl - $v['lvl'];
                $url = array_slice($url, 0, -$c);
                $urlModel->setParentUrl($url);
            }
            $lvl = $v['lvl'];
            $list[$k]['url'] = $urlModel->getUrl($v);
        }
        return $list;
    }


    public function detectCurrentCategory($path)
    {
        if (!isset($this->categoryModel)) {
            // Если категория не была определена на этапе DetectPageByUrl, то нужно
            // проверить, нет ли категории в query_string
            $this->categoryModel = new \Shop\Structure\Category\Site\Model($this->structurePath);
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


    public function getTemplatesVars()
    {
        if ($this->categoryModel->getCurrent()) {
            return $this->categoryModel->getTemplatesVars();
        } else {
            return parent::getTemplatesVars();
        }
    }

}
