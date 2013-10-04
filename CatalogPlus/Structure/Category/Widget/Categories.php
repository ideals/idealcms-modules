<?php
namespace CatalogPlus\Structure\Category\Widget;

use Ideal\Core\Db;

class Categories extends \Ideal\Core\Widget
{
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
        $db = Db::getInstance();
        $_sql = "SELECT * FROM i_catalogplus_structure_category
                    WHERE lvl=1 AND is_active=1 AND is_not_menu=0 AND structure_path='{$this->structurePath}'
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
        if ($object['structure_path'] == $this->structurePath) {
            $menu[$object['cid']]['isActivePage'] = 1;
        }
        return $menu;
    }

}