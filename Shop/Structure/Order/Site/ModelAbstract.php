<?php
namespace Shop\Structure\Order\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;

class ModelAbstract extends \Ideal\Structure\Part\Site\Model
{
    /**
     * Таблица где хранятся товары. Указывается без префикса
     * @var string
     */
    protected $tableGood = 'shop_structure_good';

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
        $table = $config->db['prefix'] . $this->tableGood;
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

    /**
     * Создание заказа
     * @param null $comment
     * @param null $fio
     * @param null $address
     * @return int
     */
    public function createOrder($comment = null, $fio = null, $address = null)
    {
        $db = Db::getInstance();
        $id = $db->insert($this->_table, array(
            'date_create' => time(),
            'comment' => $comment,
            'date_mod' => time(),
            'prev_structure' => $this->prevStructure,
            'fio' => $fio,
            'address' => $address,
            'is_active' => 1
        ));
        return $id;
    }

    /**
     * @param $letter
     * @param $id
     * @param $price
     */
    public function updateOrder(&$letter, $id, $price)
    {
        $db = Db::getInstance();
        $table = $this->_table;
        $db->update($table, $id, array(
            'content' => $letter,
            'price' => $price
        ));
    }
}
