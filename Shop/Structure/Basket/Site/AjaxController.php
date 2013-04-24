<?php
namespace Shop\Structure\Basket\Site;

class AjaxController extends \Ideal\Core\Site\AjaxController
{

    public function addToBasketAction()
    {
        $id = $_POST['goodId'];
        $amount = $_POST['goodAmount'];
        $price = $_POST['goodPrice'];
        $arr = json_decode($_POST['cookie'], true);
        if (!isset($arr[$id])) {
            $arr['count'] += 1;
        }
        $arr['total_price'] += ($price * $amount);
        $arr[$id]['amount'] += $amount;
        $arr[$id]['price'] = $price;
        print json_encode($arr);

    }

}
