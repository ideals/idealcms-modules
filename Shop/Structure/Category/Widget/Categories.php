<?php
namespace Shop\Structure\Category\Widget;

use Ideal\Core\Db;

class Categories extends \Ideal\Core\Widget
{

    public function getData()
    {
        $db = Db::getInstance();
        $_sql = "SELECT name, url, img FROM i_shop_structure_categorymulti";
        $goodGroup = $db->queryArray($_sql);

        return $goodGroup;
    }

}