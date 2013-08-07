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

        $_sql = 'SELECT * FROM i_shop_structure_category
                    WHERE (lvl = 1 OR lvl = 2) AND structure_path="1-3" AND is_active=1 AND is_not_menu=0 ORDER BY cid';
        $menuList = $db->queryArray($_sql);


        $num = 0;
        $menu = array();
        $parentUrl = '';
        foreach ($menuList as $v) {
            if ($v['lvl'] == 1) {
                $num = substr($v['cid'], 0, 3);
                $parentUrl = $v['url'];
                $v['link'] = '/products/' . $v['url'] . $config->urlSuffix;
                $menu[$num] = $v;
            } else {
                if ($v['is_active'] == 1 && $v['is_not_menu'] == 0) {
                    $v['link'] = '/products/' . $parentUrl . '/' . $v['url'] . $config->urlSuffix;
                    $menu[$num]['subMenu'][] = $v;
                }
            }
        }
        unset($menuList);

        $object = $this->model->object;
        if ($object['structure_path'] == '1-3') {
            $activeUrl = substr($object['cid'], 0, 3);
            $menu[$activeUrl]['activeUrl'] = 1;
            foreach ($menu[$activeUrl]['subMenu'] as $k => $elem) {
                if ($elem['cid'] == $object['cid']) {
                    $menu[$activeUrl]['subMenu'][$k]['activeUrl'] = 1;
                }
            }
        }

        return $menu;

    }


}