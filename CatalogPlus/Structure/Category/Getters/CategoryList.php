<?php
namespace CatalogPlus\Structure\Category\Getters;

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
        $_sql = 'SELECT ID, name FROM i_catalogplus_structure_category';
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
        $config = Config::getInstance();
        $pageData = $this->obj->getPageData();

        $_table = $config->db['prefix'] . 'catalogplus_good';

        if (isset($this->obj->fields['category_id'])) {
            // Если связь товара с категорией через поле в таблице товара
            $arr = (isset($pageData['category_id'])) ? array($pageData['category_id']) : array();
            return $arr;
        }
        $goodId = $pageData['ID'];
        $_sql = "SELECT category_id FROM {$_table} WHERE good_id='{$goodId}'";
        $arr = $db->queryArray($_sql);

        $list = array();
        foreach ($arr as $v) {
            $list[] = $v['category_id'];
        }

        return $list;
    }


    public function getSqlAdd($newValue)
    {
        $_sql = "DELETE FROM i_catalogplus_good WHERE good_id='{{ objectId }}';"
              . "INSERT INTO i_catalogplus_good SET good_id='{{ objectId }}', category_id='{$newValue}';";
        return $_sql;
    }

}
