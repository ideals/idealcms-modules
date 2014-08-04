<?php
namespace Shop\Structure\CategoryMulti\Widget;

use Ideal\Core\Db;
use Ideal\Core\Config;

class Categories2 extends \Ideal\Core\Widget
{
    protected $prevStructure;
    protected $prefix;

    public function setPrevStructure($prevStructure)
    {
        $this->prevStructure = $prevStructure;
    }


    public function setPrefix($prefix)
    {
        $prefix = explode('/', $prefix);
        foreach ($prefix as $v) {
            $path['url'] = $v;
        }
        $this->prefix = $path;
    }


    public function getData()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $_table = $config->db['prefix'] . 'catalog_structure_categorymulti';
        $_sql = "SELECT * FROM {$_table}
                    WHERE (lvl = 1 OR lvl = 2) AND is_active=1 AND is_not_menu=0 AND prev_structure='{$this->prevStructure}' ORDER BY cid";
        $menuList = $db->select($_sql);

        // Раскладываем считанное меню во вложенные массивы по cid и lvl
        $num = 0;
        $menu = array();
        $url = new \Ideal\Field\Url\Model();
        foreach ($menuList as $v) {
            if ($v['lvl'] == 1) {
                $num = $v['cid'];
                $parentUrl = $v['url'];
                $v['link'] = $url->getUrlWithPrefix($v, $this->prefix);
                $menu[$num] = $v;
            }
            if ($v['lvl'] == 2) {
                if ($v['is_active'] == 1 && $v['is_not_menu'] == 0) {
                    $prefix = $this->prefix . '/' . $parentUrl;
                    $v['link'] = $url->getUrlWithPrefix($v, $prefix);
                    $menu[$num]['subMenu'][] = $v;
                }
            }
        }
        unset($menuList);

        $page = $this->model->getPageData;
        if ($page['prev_structure'] == $this->prevStructure) {
            // todo заменить цифру 3 на params['digit']
            $activeUrl = substr($page['cid'], 0, 3);
            $menu[$activeUrl]['activeUrl'] = 1;
            foreach ($menu[$activeUrl]['subMenu'] as $k => $elem) {
                if ($elem['cid'] == $page['cid']) {
                    $menu[$activeUrl]['subMenu'][$k]['activeUrl'] = 1;
                }
            }
        }

        return $menu;
    }

}
