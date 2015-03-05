<?php
namespace CatalogPlus\Medium\CategoryList;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Medium\AbstractModel;

class Model extends AbstractModel
{
    /** @var  \Ideal\Core\Admin\Model Модель редактируемого элемента */
    protected $obj;
    protected $fieldName;

    public function getList()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'catalogplus_structure_category';
        $_sql = "SELECT ID, name, lvl FROM {$table} ORDER BY cid";
        $arr = $db->select($_sql);

        $list = array();
        foreach ($arr as $item) {
            $list[$item['ID']] = str_repeat('вЂ” ', ($item['lvl'] - 1)) . $item['name'];
        }

        return $list;
    }


    public function getValues()
    {
        $db = Db::getInstance();
        $pageData = $this->obj->getPageData();
        $goodId = $pageData['ID'];
        $_sql = "SELECT category_id FROM {$this->table} WHERE good_id='{$goodId}'";
        $arr = $db->select($_sql);

        $list = array();
        foreach ($arr as $v) {
            $list[] = $v['category_id'];
        }

        return $list;
    }


    public function getSqlAdd($newValue)
    {
        $_sql = "DELETE FROM {$this->table} WHERE good_id='{{ objectId }}';";
        foreach ($newValue as $v) {
            $_sql .= "INSERT INTO {$this->table} SET good_id='{{ objectId }}', category_id='{$v}';";
        }
        return $_sql;
    }
}
