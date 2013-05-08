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
        $_sql = 'SELECT ID, cap FROM i_articles_structure_category';
        $arr = $db->queryArray($_sql);

        $list = array();
        foreach ($arr as $item) {
            $list[$item['ID']] = $item['cap'];
        }

        return $list;
    }


    public function getVariants()
    {
        $db = Db::getInstance();
        $paperId = $this->obj->object['ID'];
        $_sql = "SELECT articles FROM i_articles_paper WHERE paper='{$paperId}'";
        $arr = $db->queryArray($_sql);

        $list = array();
        foreach ($arr as $v) {
            $list[] = $v['articles'];
        }

        return $list;
    }


    public function getSqlAdd($newValue)
    {
        $_sql = "DELETE FROM i_articles_paper WHERE paper='{{ objectId }}';"
              . "INSERT INTO i_articles_paper SET paper='{{ objectId }}', articles='{$newValue}';";
        return $_sql;
    }

}
