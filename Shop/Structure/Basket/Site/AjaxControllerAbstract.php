<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Shop\Structure\Basket\Site;

class AjaxControllerAbstract extends \Ideal\Core\AjaxController
{
    /** @var array Дополнительные HTTP-заголовки ответа  */
    public $httpHeaders = [];

    protected Model $basket;

    /**
     * Инициализация сессии
     */
    public function __construct()
    {
        if (function_exists('session_status')) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
        } elseif (session_id() === '') {
            session_start();
        }

        $this->httpHeaders['Content-type'] = 'application/json';
        $this->basket = new Model('');
    }

    /**
     * Добавление товара в корзину
     *
     * @throws \Exception
     */
    public function addGoodAction(): string
    {
        $goodId = $_REQUEST['good-id'] ?? '';
        $count = $_REQUEST['count'] ?? 0;

        $good = [
            'id' => $goodId,
            'count' => $count,
        ];

        $basketArr = $this->basket->addGood($good);

        $this->basket->saveBasketCookie();

        return json_encode([
            'error' => false,
            'text' => '',
            'basket' => $basketArr,
        ]);
    }

    /**
     * Вывод корзины в json
     */
    public function getBasketAction(): string
    {
        return json_encode(['basket' => $this->basket->saveBasketCookie()]);
    }

    /**
     * Очищает корзину
     */
    public function clearBasketAction(): string
    {
        setcookie('basket', null, -1, '/');
        setcookie('tabsInfo', null, -1, '/');
        return json_encode([
            'error' => false,
            'text' => 'Корзина очищена',
        ], JSON_THROW_ON_ERROR);
    }
}
