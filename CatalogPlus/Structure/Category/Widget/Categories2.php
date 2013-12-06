<?php
namespace CatalogPlus\Structure\Category\Widget;

use Ideal\Core\Db;
use Ideal\Core\Config;

class Categories2 extends \Ideal\Core\Widget
{
    protected $structurePath;
    protected $prefix;

    public function setPrevStructure($prevStructure)
    {
        $this->prevStructure = $prevStructure;
    }


    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }


    public function getData()
    {
        // Определяем кол-во разрядов на один уровень cid для структуры категорий
        $config = Config::getInstance();
        $category = $config->getStructureByName('CatalogPlus_Category');
        $digits = $category['params']['digits'];
        $table = $config->db['prefix'].'catalogplus_structure_category';

        $db = Db::getInstance();
        $_sql = "SELECT * FROM {$table}
                    WHERE (lvl = 1 OR lvl = 2) AND is_active=1 AND is_not_menu=0 AND prev_structure='{$this->prevStructure}' ORDER BY cid";
        $menuList = $db->queryArray($_sql);

        // Раскладываем считанное меню во вложенные массивы по cid и lvl
        $num = 0;
        $menu = array();
        $url = new \Ideal\Field\Url\Model();
        foreach ($menuList as $v) {
            if ($v['lvl'] == 1) {
                $num = substr($v['cid'], 0, $digits);
                $parentUrl = $v['url'];
                if (isset($v['url_full']) && strlen($v['url_full']) > 1) {
                    $v['link'] = 'href="' . $v['url_full'] . $config->urlSuffix . '"';
                } else {
                    $v['link'] = 'href="' . $url->getUrlWithPrefix($v, $this->prefix) . '"';
                }
                $v['subMenu'] = array();
                $menu[$num] = $v;
            }
            if ($v['lvl'] == 2) {
                $prefix = $this->prefix . '/' . $parentUrl;
                if (isset($v['url_full']) && strlen($v['url_full']) > 1) {
                    $v['link'] = 'href="' . $v['url_full'] . $config->urlSuffix . '"';
                } else {
                    $v['link'] = 'href="' . $url->getUrlWithPrefix($v, $prefix) . '"';
                }
                $menu[$num]['subMenu'][] = $v;
            }
        }
        unset($menuList);

        $object = $this->model->getPageData();
        if (isset($object['prev_structure']) && $object['prev_structure'] == $this->prevStructure) {
            $activeUrl = substr($object['cid'], 0, $digits);
            if (!isset($menu[$activeUrl])) return $menu;
            $menu[$activeUrl]['activeUrl'] = 1;
            $menu[$activeUrl]['classActiveUrl'] = 'activeMenu';
            foreach ($menu[$activeUrl]['subMenu'] as $k => $elem) {
                $elem['cid'] = rtrim($elem['cid'], '0');
                $cid = substr($object['cid'], 0, strlen($elem['cid']));
                if ($elem['cid'] == $cid) {
                    $menu[$activeUrl]['subMenu'][$k]['activeUrl'] = 1;
                }
            }
        }

        return $menu;
    }

}
