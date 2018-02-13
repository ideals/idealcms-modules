<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Shop\Medium\CategoryList;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Medium;

class Model extends Medium\AbstractModel
{

    public function  getList()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $_table = $config->db['prefix'] . 'catalog_structure_category';
        $_sql = 'SELECT ID, name FROM {$_table}';
        $arr = $db->select($_sql);

        $list = array();
        foreach ($arr as $item) {
            $list[$item['ID']] = $item['name'];
        }

        return $list;
    }


    public function getVariants()
    {
        $db = Db::getInstance();

        if (isset($this->obj->fields['category_id'])) {
            // Если связь товара с категорией через поле в таблице товара
            $arr = (isset($this->obj->object['category_id'])) ? array($this->obj->object['category_id']) : array();
            return $arr;
        }
        $pageData = $this->obj->getPageData();
        $goodId = $pageData['ID'];
        $_sql = "SELECT category_id FROM i_shop_category_good WHERE good_id='{$goodId}'";
        $arr = $db->select($_sql);

        $list = array();
        foreach ($arr as $v) {
            $list[] = $v['category_id'];
        }

        return $list;
    }


    public function getSqlAdd($newValue)
    {
        $_sql = "DELETE FROM i_shop_category_good WHERE good_id='{{ objectId }}';"
            . "INSERT INTO i_shop_category_good SET good_id='{{ objectId }}', category_id='{$newValue}';";
        return $_sql;
    }
}
