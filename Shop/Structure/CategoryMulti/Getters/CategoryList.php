<?php
namespace Shop\Structure\CategoryMulti\Getters;

use Ideal\Core\Config;
use Ideal\Core\Db;

class CategoryList
{
    /** @var  \Ideal\Core\Model */
    protected $obj;
    protected $fieldName;
    protected $categoryTable;
    protected $goodTable;
    protected $categoryGoodTable;

    public function __construct($obj, $fieldName)
    {
        $this->obj = $obj;
        $this->fieldName = $fieldName;

        $config = Config::getInstance();
        $this->categoryTable = $config->db['prefix'] . 'shop_structure_categorymulti';
        $this->goodTable = $config->db['prefix'] . 'shop_structure_good';
        $this->categoryGoodTable = $config->db['prefix'] . 'shop_categorymulti_good';
    }


    public function  getList()
    {
        $db = Db::getInstance();
        // Получаем prev_structure наших категорий товара
        $categoryPrevStructure = $this->obj->category->getPrevStructure();
        $_sql = "SELECT ID, name FROM {$this->categoryTable}
                        WHERE prev_structure='{$categoryPrevStructure}' AND is_active=1 ORDER BY cid";
        $arr = $db->queryArray($_sql);

        // todo отображение вложенных категорий
        $list = array();
        foreach ($arr as $item) {
            $list[$item['ID']] = $item['name'];
        }

        return $list;
    }


    public function getVariants()
    {
        $db = Db::getInstance();
        $good = $this->obj->getPageData();
        $_sql = "SELECT category_id FROM {$this->categoryGoodTable} WHERE good_id='{$good['ID']}'";
        $arr = $db->queryArray($_sql);

        $list = array();
        foreach ($arr as $v) {
            $list[] = $v['category_id'];
        }

        return $list;
    }


    public function getSqlAdd($newValue)
    {
        $_sql = "DELETE FROM {$this->categoryGoodTable} WHERE good_id='{{ objectId }}';"
              . "INSERT INTO {$this->categoryGoodTable} SET good_id='{{ objectId }}', category_id='{$newValue}';";
        return $_sql;
    }

    public function getWhereFilter($currentCategory)
    {
        $_sql = "ID IN (SELECT good_id FROM {$this->categoryGoodTable} WHERE category_id="
              . mysql_real_escape_string($currentCategory) . ')';
        return $_sql;
    }
}
