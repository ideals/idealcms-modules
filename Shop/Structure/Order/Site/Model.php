<?php
namespace Shop\Structure\Order\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{
    protected $table = 'shop_structure_good';
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
        foreach ($basket['good'] as $key => $value) {
            if ($key == 'count' OR $key == 'total_price') continue;
            $in .= $key . ',';
        }
        $in = substr($in, 0, strlen($in) - 1);
        $in .= ")";

        $config = Config::getInstance();
        $table = $config->db['prefix'] . $this->table;;
        $_sql = "SELECT * FROM {$table} WHERE ID IN {$in}";
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