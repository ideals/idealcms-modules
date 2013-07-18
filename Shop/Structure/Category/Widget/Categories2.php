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

        $path = $this->model->getPath();
        end($path);
        $parent = (count($path) > 3) ? prev($path) : array();
        foreach ($result as $v) {
            $k = $v['cid'];
            if ($parent['cid'] == $v['cid']) {
                $current = end($path);
                $cid = rtrim($v['cid'], '0');
                $_sql = 'SELECT * FROM i_shop_structure_category WHERE lvl=2 AND cid LIKE \'' . $cid . '%\' ORDER BY cid';
                $result = $db->queryArray($_sql);
                foreach ($result as $val) {
                    if ($val['cid'] == $current['cid']) $arr[$k]['subMenu'][$val['cid']]['activeUrl'] = true;
                    $val['link'] = '/' . reset($url) . '/' . $catUrl . '/' . $arr[$cid]['url'] . '/' . $v['url'] . $config->urlSuffix;
                    $arr[$k]['subMenu'][$val['cid']] = $val;
                }
            }
            if (end($url) == $v['url']) {
                if ($v['lvl'] == 1 || $arr[$cid]['url'] == prev($url)) {
                    $v['activeUrl'] = true;
                }
            }
            $v['link'] = '/' . reset($url) . '/' . $catUrl . '/' . $v['url'] . $config->urlSuffix;
            $arr[$k] = (isset($arr[$k])) ? array_merge($v, $arr[$k]) : $v;
        }
        return $arr;
    }


}