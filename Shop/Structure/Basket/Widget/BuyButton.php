<?php
/**
 * Передаёт данные для кнопки покупки
 */

namespace Shop\Structure\Basket\Widget;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Core\Util;

class BuyButton extends \Ideal\Core\Widget
{
    private $goodId;
    private $goodPrice;

    /**
     * Устанавливает идентификатор товара и его стоимость,
     * чтобы корректно отобразить данные кнопки заказа
     * @param $id Идентификатор товара
     * @param $price Стоимость одного товара
     */
    public function setGoodId($id, $price)
    {
        $this->goodId = $id;
        $this->goodPrice = $price;
    }


    /**
     * Передача всех данных кнопки заказа
     * @return array
     */
    public function getData()
    {
        $button = array(
            'goodId' => $this->goodId,
            'goodPrice' => $this->goodPrice,
            'goodAmount' => 0 // количество этого товара в корзине
        );

        return $button;
    }

}