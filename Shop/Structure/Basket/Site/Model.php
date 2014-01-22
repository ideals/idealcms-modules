<?php
namespace Shop\Structure\Basket\Site;

use Ideal\Core\Db;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{
    protected $table = 'i_catalogplus_structure_good';

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

        $in = array();
        foreach ($basket as $key => $value) {
            if ($key == 'count' OR $key == 'total_price') continue;
            if (!empty($value['price'])) $in[] = $key;
        }

        if (count($in) === 0) {
            return $in;
        }

        $in = '(' . implode(',', $in) . ')';

        $_sql = "SELECT * FROM {$this->table} WHERE ID IN {$in}";
        $goodIdsArr = $db->queryArray($_sql);
        //$basket = (array)$basket;
        foreach ($goodIdsArr as $good) {
            $id = $good['ID'];
            $basket[$id]['name'] = $good['name'];
            $basket[$id]['price'] = $good['price'];
            $basket[$id]['amount'] = $basket['good'][$id]['count'];
            $basket[$id]['total_price'] = $basket[$id]['price'] * $basket[$id]['amount'];
            $basket[$id]['img'] = $good['img'];
            $basket[$id]['url'] = $good['url'];
            $basket[$id]['ID'] = $id;
        }
        return $basket;
    }


    public function getStructureElements()
    {
        return array();
    }

}
