<?php

namespace CatalogPlus\Structure\Category\Widget;

use Ideal\Field\Url\Model;
use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Core\Widget;

class Categories extends Widget
{
    public function setPrevStructure($prevStructure): void
    {
        $this->prevStructure = $prevStructure;
    }

    public function setPrefix($prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * @return mixed[]
     */
    public function getData($limit = 5): array
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'catalogplus_structure_category';
        $_sql = "SELECT * FROM {$table}
                    WHERE lvl=1 AND is_active=1 AND is_not_menu=0 AND prev_structure='{$this->prevStructure}'
                    ORDER BY cid LIMIT {$limit}";
        $menuList = $db->select($_sql);

        $menu = [];
        $url = new Model();
        foreach ($menuList as $v) {
            $v['link'] = $url->getUrlWithPrefix($v, $this->prefix);
            $menu[$v['cid']] = $v;
        }

        unset($menuList);

        $object = $this->model->getPageData();
        if (
            isset($object['prev_structure'])
            && $object['prev_structure'] == $this->prevStructure
            && isset($object['cid'])
            && isset($menu[$object['cid']])
        ) {
            $menu[$object['cid']]['isActivePage'] = 1;
        }

        return $menu;
    }
}
