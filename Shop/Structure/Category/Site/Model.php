<?php
namespace Shop\Structure\Category\Site;

use Ideal\Core\Db;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{
    private $limit = 20;

    public function getList($page, $onPage)
    {
        if (!isset($this->object['id_1c'])) {
            //return array();
        }
        $page = ($_GET['page']) ? $_GET['page'] : 1;
        $from = $this->limit * ($page - 1);
        $db = Db::getInstance();
        $categoryId = $this->object['ID'];
        $_sql = "SELECT t1.*,t2.name AS brand FROM i_shop_structure_good AS t1, i_shop_structure_type AS t2
                    WHERE t2.id=t1.idBrand AND idCategory='{$categoryId}' LIMIT {$from}, {$this->limit}";
        //$_sql = "SELECT * FROM i_shop_structure_good WHERE idCategory='{$categoryId}' LIMIT {$from}, {$this->limit}";
        $goods = $db->queryArray($_sql);

        return $goods;
    }


    public function pagginator(){
        $db = Db::getInstance();
        $categoryId = $this->object['ID'];
        $_sql = "SELECT COUNT(*) FROM i_shop_structure_good WHERE idCategory='{$categoryId}'";
        $count = $db->queryArray($_sql);
        $count = reset($count[0]) / $this->limit;
        return ceil($count);
    }

}