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

    /**
     * @return string[]
     */
    public function getList(): array
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'catalogplus_structure_category';
        $_sql = sprintf('SELECT ID, name, lvl FROM %s ORDER BY cid', $table);
        $arr = $db->select($_sql);

        $list = [];
        foreach ($arr as $item) {
            $list[$item['ID']] = str_repeat('-', ($item['lvl'] - 1)) . $item['name'];
        }

        return $list;
    }


    public function getValues(): array
    {
        $db = Db::getInstance();
        $list = [];
        $pageData = $this->obj->getPageData();
        if (!empty($pageData) && isset($pageData['ID'])) {
            $goodId = $pageData['ID'];
            $_sql = sprintf("SELECT category_id FROM %s WHERE good_id='%s'", $this->table, $goodId);
            $arr = $db->select($_sql);
            foreach ($arr as $v) {
                $list[] = $v['category_id'];
            }
        }

        return $list;
    }


    public function getSqlAdd($newValue): string
    {
        $_sql = sprintf("DELETE FROM %s WHERE good_id='{{ objectId }}';", $this->table);
        if ($newValue) {
            foreach ($newValue as $v) {
                $_sql .= sprintf("INSERT INTO %s SET good_id='{{ objectId }}', category_id='%s';", $this->table, $v);
            }
        }

        return $_sql;
    }
}
