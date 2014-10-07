<?php
namespace Shop\Structure\Basket\Site;

class AjaxControllerAbstract extends \Ideal\Core\AjaxController
{

    public function addToBasketAction()
    {
        $id = $_POST['goodId'];
        $amount = (int)$_POST['goodAmount'];
        $price = (int)$_POST['goodPrice'];
        $arr = json_decode($_POST['cookie'], true);
        $arr['amount'] += 1;
        $arr['price'] += ($price * $amount);
        if (isset($arr[$id]['amount'])) {
            $arr[$id]['amount'] += $amount;
        } else {
            $arr[$id]['amount'] = $amount;
        }
        $arr[$id]['price'] = $price;
        print json_encode($arr);
    }

    public function delBasketAction()
    {
        $arr = json_decode($_POST['cookie'], true);
        $amount = (int)$arr[$_POST['did']]['amount'];
        $price = (int)$arr[$_POST['did']]['price'];

        unset($arr[$_POST['did']]);
        if (count($arr) == 2) {
            $arr['total_price'] = 0;
            $arr['count'] = 0;
        } else {
            $arr['total_price'] -= $amount * $price;
            $arr['count'] -= 1;
        }
        print json_encode($arr);
    }

}
