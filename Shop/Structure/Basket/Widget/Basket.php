<?php
/**
 * Виджет, отображающий содержимое корзины
 */

namespace Shop\Structure\Basket\Widget;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Core\Util;

class Basket extends \Ideal\Core\Widget
{

    public function getData()
    {
        // TODO экранировать получене данных от кук, это уязвимость
        $basket = array(
            'amount' => 0,
            'price' => 0
        );
        if (isset($_COOKIE['basket']))
            $basket = json_decode($_COOKIE['basket'], true);
        return $basket;
    }

}