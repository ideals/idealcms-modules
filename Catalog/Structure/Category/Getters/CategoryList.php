<?php

namespace Catalog\Structure\Category\Getters;

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


    /**
     * @return mixed[]
     */
    public function getList(): array
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $_table = $config->db['prefix'] . 'catalog_structure_category';
        $_sql = 'SELECT ID, name FROM ' . $_table;
        $arr = $db->select($_sql);

        $list = [];
        foreach ($arr as $item) {
            $list[$item['ID']] = $item['name'];
        }

        return $list;
    }


    /**
     * @return mixed[]
     */
    public function getVariants(): array
    {
        $db = Db::getInstance();

        $pageData = $this->obj->getPageData();

        $goodId = $pageData['ID'];
        $config = Config::getInstance();
        $_table = $config->db['prefix'] . 'shop_category_good';
        $_sql = sprintf("SELECT category_id FROM %s WHERE good_id='%s'", $_table, $goodId);
        $arr = $db->select($_sql);

        $list = [];
        foreach ($arr as $v) {
            $list[] = $v['category_id'];
        }

        return $list;
    }


    public function getSqlAdd(string $newValue): string
    {
        return "DELETE FROM i_shop_category_good WHERE good_id='{{ objectId }}';"
              . sprintf("INSERT INTO i_shop_category_good SET good_id='{{ objectId }}', category_id='%s';", $newValue);
    }

}
