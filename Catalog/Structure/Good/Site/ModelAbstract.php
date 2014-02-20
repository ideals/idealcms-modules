<?php
namespace Catalog\Structure\Good\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Field\Url;

class ModelAbstract extends \Ideal\Core\Site\Model
{
    public function getWhere($where)
    {
        $where = parent::getWhere($where . ' AND e.is_active=1');
        return $where;
    }

    public function detectPageByUrl($path, $url)
    {
        if (count($url) > 1) {
            $this->is404 = true;
            return $this;
        }

        $db = Db::getInstance();

        $url = mysql_real_escape_string($url[0]);
        $_sql = "SELECT * FROM {$this->_table} WHERE url='{$url}' LIMIT 1";

        $list = $db->queryArray($_sql); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($list[0]['ID'])) {
            $this->path = $path;
            $this->is404 = true;
            return $this;
        }
        $list[0]['structure'] = 'Catalog_Good';

        $this->path = array_merge($path, $list);

        $_REQUEST['action'] = 'detail'; // раз до сюда добрались - это подробное описание товара

        return $this;
    }

    public function getList($page)
    {
        $list = parent::getList($page);

        // Построение правильных URL
        $url = new \Ideal\Field\Url\Model();
        $url->setParentUrl($this->path);
        if (is_array($list) && count($list) != 0 ) {
            foreach ($list as $k => $v) {
                $list[$k]['link'] = $url->getUrl($v);
            }
        }

        return $list;
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


    public function detectPath()
    {
        $good = $this->getPageData();

        $category = new \Catalog\Structure\Category\Site\Model('');
        $category->setPageDataById($good['category_id']);
        $path = $category->detectPath();

        return $path;
    }
}
