<?php
namespace Shop\Structure\CategoryMulti\Site;

use Ideal\Core\Db;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{
    public function getGoods()
    {
        $page = ($_GET['page']) ? $_GET['page'] : 1;
        $from = $this->limit * ($page - 1);;

        $db = Db::getInstance();
        $categoryId = $this->object['ID'];
       // $_sql = "SELECT * FROM i_shop_structure_categorymulti AS ssc WHERE ssc.ID IN (SELECT  scg.good_id FROM i_shop_category_good AS scg WHERE scg.category_id ='{$categoryId}')";
        /*$_sql = "SELECT * FROM i_shop_structure_categorymulti AS ssc
                    JOIN i_shop_category_good AS scg ON ssc.ID = scg.good_id WHERE scg.category_id = '{$categoryId}' LIMIT {$from}, {$this->limit}";*/

        $_sql = "SELECT * FROM i_shop_structure_good AS ssg LEFT JOIN i_shop_category_good AS scg ON scg.good_id = ssg.id WHERE scg.category_id = '{$categoryId}' LIMIT {$from}, {$this->limit}";
        $goods = $db->select($_sql);
        return $goods;
    }
}