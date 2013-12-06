<?php
namespace CatalogPlus\Structure\Category\Widget;

use Ideal\Core\Db;
use Ideal\Core\Config;

class Categories extends \Ideal\Core\Widget
{
    public function setStructurePath($prevStructure)
    {
        $this->prevStructurePath = $prevStructure;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function getData()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $config->db['prefix'].'catalogplus_structure_category';
        $_sql = "SELECT * FROM {$table}
                    WHERE lvl=1 AND is_active=1 AND is_not_menu=0 AND prev_structure='{$this->prevStructure}'
                    ORDER BY cid";
        $menuList = $db->queryArray($_sql);

        $menu = array();
        $url = new \Ideal\Field\Url\Model();
        foreach ($menuList as $v) {
            $v['link'] = $url->getUrlWithPrefix($v, $this->prefix);
            $menu[$v['cid']] = $v;
        }
        unset($menuList);

        $object = $this->model->object;
        if ($object['prev_structure'] == $this->prevStructure) {
            $menu[$object['cid']]['isActivePage'] = 1;
        }
        return $menu;
    }

}