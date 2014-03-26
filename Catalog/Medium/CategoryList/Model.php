<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Catalog\Medium\CategoryList;

use Ideal\Medium\AbstractModel;
use Ideal\Core\Db;
use Ideal\Core\Config;

class Model extends AbstractModel
{
    public function  getList()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $_table = $config->db['prefix'] . 'catalog_structure_category';
        $_sql = 'SELECT ID, name FROM ' . $_table;
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

        $pageData = $this->obj->getPageData();
        if (isset($this->obj->fields['category_id'])) {
            // Если связь товара с категорией через поле в таблице товара
            $arr = (isset($pageData['category_id'])) ? array($pageData['category_id']) : array();
            return $arr;
        }
        $goodId = $pageData['ID'];
        $config = Config::getInstance();
        $_table = $config->db['prefix'] . 'shop_category_good';
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
        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'articles_category_article';
        $_sql = "DELETE FROM {$table} WHERE article_id='{{ objectId }}';"
            . "INSERT INTO {$table} SET article_id='{{ objectId }}', category_id='{$newValue}';";
        return $_sql;
    }

}
