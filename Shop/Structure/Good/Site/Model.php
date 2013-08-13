<?php
namespace Shop\Structure\Good\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Field\Url;
use Ideal\Core\Pagination;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{

    public function getListByCategory($page, $categoryId)
    {
        $onPage = $this->params['elements_site'];

        $page = ($page == 0) ? 1 : $page;
        $from = $onPage * ($page - 1);
        $db = Db::getInstance();

        if (isset($this->fields['category_id'])) {
            $_sql = "SELECT * FROM {$this->_table} WHERE structure_path={$this->structurePath}
                        AND category_id={$categoryId}";
        } else {
            $_sql = "SELECT * FROM {$this->_table} AS g LEFT JOIN i_shop_category_good AS cg ON cg.good_id = g.ID
                    WHERE cg.category_id = '{$categoryId}'";
        }
        $_sql .= " AND is_active=1 ORDER BY {$this->params['field_sort']} LIMIT {$from}, {$onPage}";
        $goods = $db->queryArray($_sql);

        return $goods;
    }


    public function getPager($page, $query)
    {
        $page = ($page == 0) ? 1 : $page;
        $onPage = $this->params['elements_site'];
        $countList = $this->getListCount();

        $pagination = new Pagination();
        $pager['pages'] = $pagination->getPages($countList,
            $onPage, $page, $query, 'page');
        $pager['prev'] = $pagination->getPrev();
        $pager['next'] = $pagination->getNext();

        return $pager;
    }


    public function detectPageByUrl($url, $path)
    {
        $db = Db::getInstance();

        $_sql = "SELECT * FROM {$this->_table} WHERE url='{$url}' LIMIT 1";

        $list = $db->queryArray($_sql); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($list[0]['ID'])) {
            return '404';
        }
        $list[0]['structure'] = 'Shop_Good';

        $this->path = array_merge($path, $list);
        $this->object = end($list);

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

}
