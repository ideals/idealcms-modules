<?php
namespace Shop\Structure\Category\Widget;

use Ideal\Core\Db;
use Ideal\Core\Config;

class Categories2 extends \Ideal\Core\Widget
{

    public function getData()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $arr = array();
        $url = explode('/', $_GET['url']);

        $_sql = 'SELECT url FROM i_ideal_structure_datalist WHERE parent_url = \'' . reset($url) . '\' LIMIT 1';
        $catUrl = $db->queryArray($_sql);
        $catUrl = $catUrl[0]['url'];


        $_sql = 'SELECT * FROM i_shop_structure_category WHERE lvl = 1 ORDER BY cid';
        $result = $db->queryArray($_sql);
        $cid = '';

        $parent = end($this->model->getPath());
        foreach ($result as $v) {
            $k = $v['cid'];
            if (end($url) == $v['url']) {
                if ($v['lvl'] == 1 || $arr[$cid]['url'] == prev($url)){
                    $v['activeUrl'] = true;
                }
            }
            if ($v['lvl'] == 2) {
                if ($cid != substr($k, 0, 3)) {
                    continue;
                } else {
                    $v['link'] = '/' . reset($url) . '/' . $catUrl . '/' . $arr[$cid]['url'] . '/' . $v['url'] . $config->urlSuffix;
                    $arr[$cid]['subMenu'][] = $v;
                    $arr[$cid]['activeUrl'] = true;
                    continue;
                }
            }
            $v['link'] = '/' . reset($url) . '/' . $catUrl . '/' . $v['url'] . $config->urlSuffix;
            $arr[$k] = $v;
        }
        return $arr;
    }


}