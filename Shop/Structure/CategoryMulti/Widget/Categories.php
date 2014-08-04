<?php
namespace Shop\Structure\Category\Widget;

use Ideal\Core\Db;

class Categories extends \Ideal\Core\Widget
{

    public function getData()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $_table = $config->db['prefix'] . 'catalog_structure_categorymulti';
        $_sql = "SELECT name, url, img FROM {$_table}";
        $goodGroup = $db->select($_sql);

        return $goodGroup;
    }

}