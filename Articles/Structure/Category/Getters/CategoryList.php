<?php
namespace Articles\Structure\Category\Getters;

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
        $_sql = 'SELECT ID, name FROM i_articles_structure_category';
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
        $article = $this->obj->getPageData();
        $_sql = "SELECT category_id FROM i_articles_category_article WHERE article_id='{$article['ID']}'";
        $arr = $db->select($_sql);

        $list = array();
        foreach ($arr as $v) {
            $list[] = $v['category_id'];
        }

        return $list;
    }


    public function getSqlAdd($newValue)
    {
        $_sql = "DELETE FROM i_articles_category_article WHERE article_id='{{ objectId }}';"
              . "INSERT INTO i_articles_category_article SET article_id='{{ objectId }}', category_id='{$newValue}';";
        return $_sql;
    }

}
