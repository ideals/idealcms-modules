<?php
namespace Shop\Structure\Category\Widget;

use Ideal\Core\Db;

class Categories {

    public function getCategories()
    {
        $db = Db::getInstance();
        $_sql = "SELECT name, url, img FROM i_shop_structure_category";
        $goodGroup = $db->queryArray($_sql);

        return $goodGroup;
    }

}