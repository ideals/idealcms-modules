<?php
namespace Shop\Structure\Basket\Site;

use Ideal\Core\Db;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{
    public function getGoods()
    {
        $db = Db::getInstance();
        $basket = $_COOKIE['basket'];
        $basket = json_decode($basket);
        $in = "(";
        foreach($basket as $key=>$value){
            if($key == 'count' OR $key == 'total_price') continue;
            $in .= $key.',';
        }
        $in = substr($in, 0, strlen($in)-1);
        $in .= ")";

        $_sql = "SELECT * FROM tablename WHERE id IN {$in}";
        $goodIdsArr = $db->queryArray($_sql);
        return '15';
    }
}