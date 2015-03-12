<?php
namespace CatalogPlus\Structure\Brand\Widget;

use \Ideal\Core\Config;
use \Ideal\Core\Db;
use \Ideal\Field;

class TypeList extends \Ideal\Core\Widget
{
    protected $prefix = '';
    protected $prevStructure = '0-0';

    public function setPrevStructure($prevStr)
    {
        $this->prevStructure = $prevStr;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function getData($url = null)
    {
        $db = Db::getInstance();
        $config = Config::getInstance();

        // Считываем список категорий продукции
        $table = $config->db['prefix'] . 'catalogplus_structure_brand';
        $_sql = "SELECT *
                 FROM {$table}
                 WHERE is_active=1 AND prev_structure='{$this->prevStructure}'
                 ORDER BY name";
        $menuList = $db->select($_sql);
        $menu = array();
        foreach ($menuList as $k => $v) {
            $menu[$k]['name'] = $v['name'];
            if (($url == $v['url'])) {
                $menu[$k]['isActivePage'] = true;
                continue;
            }
            $menu[$k]['link'] = 'href="' . $this->prefix . '/' . $v['url'] . $config->urlSuffix . '"';
        }
        return $menu;
    }
}