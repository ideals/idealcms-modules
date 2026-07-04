<?php

/**
 * Передаёт данные для кнопки покупки
 */

namespace Shop\Structure\Basket\Widget;

use Ideal\Core\Widget;

class BuyButton extends Widget
{
    private $goodId;

    private $goodPrice;

    /**
     * Устанавливает идентификатор товара и его стоимость,
     * чтобы корректно отобразить данные кнопки заказа
     * @param $id Идентификатор товара
     * @param $price Стоимость одного товара
     */
    public function setGoodId($id, $price): void
    {
        $this->goodId = $id;
        $this->goodPrice = $price;
    }


    /**
     * Передача всех данных кнопки заказа
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return [
            'goodId' => $this->goodId,
            'goodPrice' => $this->goodPrice,
            'goodAmount' => 0, // количество этого товара в корзине
        ];
    }

}
