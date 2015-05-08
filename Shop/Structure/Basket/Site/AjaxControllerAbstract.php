<?php
namespace Shop\Structure\Basket\Site;

use Ideal\Core\Db;

class AjaxControllerAbstract extends \Ideal\Core\AjaxController
{
    private $answer;
    private $basket;
    private $update;

    private $idGood;
    private $quant;

    // TODO определять таблицы с товарами и предложениями в конструкторе
    private $tableGood;
    private $tableOffer;

    public function __construct()
    {
        $this->answer = array(
            'error' => false,
            'text' => ''
        );
        if (isset($_REQUEST['add-to-cart'])) {
            $this->idGood = $_REQUEST['add-to-cart'];
        }
        if (isset($_REQUEST['quantity'])) {
            $this->quant = $_REQUEST['quantity'];
        }
        $this->update = false;
        if (isset($_COOKIE['basket'])) {
            $this->basket = json_decode($_COOKIE['basket'], true);
            if ((time() - $this->basket['versi']) > 7199) {
                $this->update = true;
            }
        } else {
            $this->basket = array(
                'goods' => array(),
                'price' => 0,
                'count' => 0,
                'versi' => time(),
                'disco' => 0,
                'total' => 0
            );
        }

    }

    public function __destruct()
    {
        if ($this->update) {
            $goods = $this->basket['goods'];
            $this->basket = array(
                'goods' => array(),
                'price' => 0,
                'count' => 0,
                'disco' => 0,
                'total' => 0
            );
            foreach ($goods as $k => $v) {
                $this->idGood = $k;
                $this->quant = $v['count'];
                $this->addGoodAction();
                unset($goods[$k]);
            }
            $this->basket['versi'] = time();
        }
        $this->answer['basket'] = $this->basket;
        print json_encode($this->answer);
        exit();
    }

    public function addGoodAction()
    {
        $id = $good = $this->idGood;
        $quant = $this->quant;
        if (!($quant > 0)) {
            return;
        }
        $good = explode('_', $good);
        if (count($good) > 1) {
            $good = $this->getGoodInfo($good[0], $good[1]);
        } else {
            $good = $this->getGoodInfo($good[0]);
        }
        if (count($good) === 0) {
            return;
        }
        if (isset($this->basket['goods'][$id])) {
            $this->basket['goods'][$id]['count'] += $quant;
        } else {
            $this->basket['goods'][$id] = array(
                'price' => $good['price'],
                'count' => $quant,
                'sale_price' => $good['sale_price']
            );
            $this->basket['count'] += 1;
        }
        $this->basket['price'] += ($quant * $good['price']);
        $this->basket['total'] += ($quant * $good['sale_price']);
        $this->basket['disco'] += ($quant * $good['discount']);
    }

    public function delGoodAction()
    {
        unset($this->basket['goods'][$this->idGood]);
        $this->update = true;
    }

    public function quantGoodAction()
    {
        $this->basket['goods'][$this->idGood]['count'] = (int)$this->quant;
        $this->update = true;
    }

    /**
     * Функция на запрос состояние корзины
     */
    public function getBasketAction()
    {
        exit();
    }

    /**
     * TODO Удаление корзины полностью
     */
    public function delBasket()
    {

    }

    /**
     * Получение информации о товара(предложении) его цена и цена с учетом скидки
     *
     * @param $id
     * @param bool $offer
     * @return mixed
     */
    private function getGoodInfo($id, $offer = false)
    {
        $db = Db::getInstance();
        if ($offer === false) {
            // TODO запрос в базу на получение информации о конкретном товаре
            $sql = "SELECT e.price, (CEIL(((100-e.sale)/100)*e.price)) AS sale_price
                    FROM i_catalogplus_structure_good AS e
                    WHERE ID = {$id} AND e.is_active = 1
                    LIMIT 1";
        } else {
            // TODO запрос в базу на получение информации о конкретном предложении для товара
            $sql = "SELECT o.price, (CEIL(((100-o.sale)/100)*o.price)) AS sale_price
                    FROM i_catalogplus_structure_offer AS o
                    WHERE o.ID = {$offer} AND o.is_active = 1
                    LIMIT 1";
        }
        $allPrice = $db->select($sql);
        $allPrice[0]['discount'] = $allPrice['price'] - $allPrice['sale_price'];
        return $allPrice[0];
    }

}
