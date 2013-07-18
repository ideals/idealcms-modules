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

        $_sql = 'SELECT * FROM i_shop_structure_category
                    WHERE (lvl = 1 OR lvl = 2) AND structure_path="1-3" ORDER BY cid';
        $menuList = $db->queryArray($_sql);


        $num = 0;
        $menu = array();
        $parentUrl = '';
        $isShowSubMenu = false;
        foreach ($menuList as $v) {
            if ($v['lvl'] == 1) {
                if ($v['is_active'] == 1 && $v['is_not_menu'] == 0) {
                    $num = rtrim($v['cid'], '0');
                    $parentUrl = $v['url'];
                    $v['link'] = '/products/' . $v['url'] . '.html';
                    $menu[$num] = $v;
                    $isShowSubMenu = true;
                } else {
                    $isShowSubMenu = false;
                }
            } else {
                if ($isShowSubMenu) {
                    if ($v['is_active'] == 1 && $v['is_not_menu'] == 0) {
                        $v['link'] = '/products/' . $parentUrl . '/' . $v['url'] . '.html';
                        $menu[$num]['subMenu'][] = $v;
                    }
                }
            }
        }

        $path = $this->model->getPath();
        $object = $this->model->object;
        if ($object['structure_path'] == '1-3') {
            $activeUrl = rtrim($object['cid'], '0');
            $activeUrl = str_split($activeUrl, 3);
            $menu[$activeUrl[0]]['activeUrl'] = 1;
            foreach ($menu[$activeUrl[0]]['subMenu'] as $k => $elem) {
                if($elem['cid'] == $object['cid']){
                    $menu[$activeUrl[0]]['subMenu'][$k]['activeUrl'] = 1;
                }
            }
        }

        return $menu;

    }


}