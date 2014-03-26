<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Articles\Medium\CategoryList;

use Ideal\Medium\AbstractModel;
use Ideal\Core\Db;
use Ideal\Core\Config;


class Model extends AbstractModel
{
    public function  getList()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'articles_structure_category';
        $_sql = 'SELECT ID, name FROM ' . $table;
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
        $table = $config->db['prefix'] . 'articles_category_article';
        $article = $this->obj->getPageData();
        $_sql = "SELECT category_id FROM {$table} WHERE article_id='{$article['ID']}'";
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
