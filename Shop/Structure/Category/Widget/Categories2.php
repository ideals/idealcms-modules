<?php
namespace Shop\Structure\Category\Widget;

use Ideal\Core\Db;
use Ideal\Core\Config;

class Categories2 extends \Ideal\Core\Widget
{
    protected $structurePath;
    protected $prefix;

    public function setStructurePath($structurePath)
    {
        $this->structurePath = $structurePath;
    }


    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }


    public function getData()
    {
        // Определяем кол-во разрядов на один уровень cid для структуры категорий
        $config = Config::getInstance();
        $category = $config->getStructureByName('Shop_Category');
        $digits = $category['params']['digits'];

        $db = Db::getInstance();
        $_sql = "SELECT * FROM i_shop_structure_category
                    WHERE (lvl = 1 OR lvl = 2) AND is_active=1 AND is_not_menu=0 AND structure_path='{$this->structurePath}' ORDER BY cid";
        $menuList = $db->queryArray($_sql);

        // Раскладываем считанное меню во вложенные массивы по cid и lvl
        $num = 0;
        $menu = array();
        $url = new \Ideal\Field\Url\Model();
        foreach ($menuList as $v) {
            if ($v['lvl'] == 1) {
                $num = substr($v['cid'], 0, $digits);
                $parentUrl = $v['url'];
                $v['link'] = $url->getUrlWithPrefix($v, $this->prefix);
                $v['subMenu'] = array();
                $menu[$num] = $v;
            }
            if ($v['lvl'] == 2) {
                $prefix = $this->prefix . '/' . $parentUrl;
                $v['link'] = $url->getUrlWithPrefix($v, $prefix);
                $menu[$num]['subMenu'][] = $v;
            }
        }
        unset($menuList);

        $object = $this->model->object;
        if ($object['structure_path'] == $this->structurePath) {
            $activeUrl = substr($object['cid'], 0, $digits);
            $menu[$activeUrl]['activeUrl'] = 1;
            $menu[$activeUrl]['classActiveUrl'] = 'activeMenu';
            foreach ($menu[$activeUrl]['subMenu'] as $k => $elem) {
                if ($elem['cid'] == $object['cid']) {
                    $menu[$activeUrl]['subMenu'][$k]['activeUrl'] = 1;
                }
            }
        }

        return $menu;
    }

}
