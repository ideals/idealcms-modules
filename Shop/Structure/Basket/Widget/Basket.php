<?php

/**
 * Виджет, отображающий содержимое корзины
 */

namespace Shop\Structure\Basket\Widget;

use Ideal\Core\Widget;

class Basket extends Widget
{
    public function getData()
    {
        // TODO экранировать получене данных от кук, это уязвимость
        $basket = [
            'amount' => 0,
            'price' => 0,
        ];
        if (isset($_COOKIE['basket'])) {
            $basket = json_decode($_COOKIE['basket'], true);
        }

        return $basket;
    }

}
