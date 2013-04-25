<?php
namespace Shop\Structure\Order\Site;

use Ideal\Core\Db;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{
    public function getGoods()
    {
        if (isset($_COOKIE)) {
            $basket = $_COOKIE['basket'];
            $basket = json_decode($basket, true);
        } else {
            $basket['total_price'] = 0;
            $basket['count'] = 0;
            return $basket;
        }
        if (count($basket) <= 2) {
            return $basket;
        }
        $db = Db::getInstance();

        $in = "(";
        foreach ($basket as $key => $value) {
            if ($key == 'count' OR $key == 'total_price') continue;
            $in .= $key . ',';
        }
        $in = substr($in, 0, strlen($in) - 1);
        $in .= ")";

        $_sql = "SELECT * FROM i_shop_structure_good WHERE id IN {$in}";
        $goodIdsArr = $db->queryArray($_sql);
        foreach ($goodIdsArr as $good) {
            $id = $good['ID'];
            $basket[$id]['name'] = $good['name'];
            $basket[$id]['total_price'] = $basket[$id]['price'] * $basket[$id]['amount'];
            $basket[$id]['img'] = $good['img'];
            $basket[$id]['url'] = $good['url'];
        }
        return $basket;
    }
}