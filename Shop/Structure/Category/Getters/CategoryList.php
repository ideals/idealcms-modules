<?php
namespace Shop\Structure\Category\Getters;

use Ideal\Core\Config;
use Ideal\Core\Db;

class CategoryList
{
    protected $obj;
    protected $fieldName;

    public function __construct($obj, $fieldName)
    {
        $this->obj = $obj;
        $this->fieldName = $fieldName;
    }


    public function  getList()
    {
        $db = Db::getInstance();
        $_sql = 'SELECT ID, cap FROM i_shop_structure_category';
        $arr = $db->queryArray($_sql);

        $list = array();
        foreach ($arr as $item) {
            $list[$item['ID']] = $item['name'];
        }

        return $list;
    }


    public function getVariants()
    {
        $db = Db::getInstance();
        $goodId = $this->obj->object['ID'];
        $_sql = "SELECT category_id FROM i_good_category WHERE good_id='{$goodId}'";
        $arr = $db->queryArray($_sql);

        $list = array();
        foreach ($arr as $v) {
            $list[] = $v['category_id'];
        }

        return $list;
    }


    public function getSqlAdd($newValue)
    {
        $_sql = "DELETE FROM i_good_category WHERE good_id='{{ objectId }}';"
              . "INSERT INTO i_good_category SET good_id='{{ objectId }}', category_id='{$newValue}';";
        return $_sql;
    }

}
