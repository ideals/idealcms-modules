<?php
namespace Catalog\Structure\Category\Widget;

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
        $config = Config::getInstance();
        $_table = $config->db['prefix'] . 'catalog_structure_category';
        $_sql = "SELECT * FROM {$_table}
                    WHERE lvl=1 AND is_active=1 AND is_not_menu=0 AND prev_structure='{$this->structurePath}'
                    ORDER BY cid";
        $menuList = $db->queryArray($_sql);

        $menu = array();
        $url = new \Ideal\Field\Url\Model();
        foreach ($menuList as $v) {
            $v['link'] = $url->getUrlWithPrefix($v, $this->prefix);
            $menu[$v['cid']] = $v;
        }
        unset($menuList);

        $pageData = $this->model->getPageData;
        if ($pageData['structure_path'] == $this->structurePath) {
            $menu[$pageData['cid']]['isActivePage'] = 1;
        }
        return $menu;
    }

}