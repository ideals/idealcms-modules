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
    /** @var Model Дополнительные HTTP-заголовки ответа  */
    protected $basket;

    /** @var array Дополнительные HTTP-заголовки ответа  */
    public $httpHeaders = array();

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
     * @return string
     * @throws \Exception
     */
    public function addGoodAction()
    {
        $goodId = isset($_REQUEST['good-id']) ? $_REQUEST['good-id'] : '';
        $count = isset($_REQUEST['count']) ? $_REQUEST['count'] : 0;

        $good = array(
            'id' => $goodId,
            'count' => $count,
        );

        $basketArr = $this->basket->addGood($good);

        $this->basket->saveBasketCookie();

        return json_encode(array(
            'error' => false,
            'text' => '',
            'basket' => $basketArr,
        ));
    }

    /**
     * Вывод корзины в json
     */
    public function getBasketAction()
    {
        return json_encode(array('basket' => $this->basket->saveBasketCookie()));
    }

    /**
     * Очищает корзину
     */
    public function clearBasketAction()
    {
        setcookie('basket', null, -1, '/');
        setcookie('tabsInfo', null, -1, '/');
        return json_encode(array(
            'error' => false,
            'text' => 'Корзина очищена',
        ));
    }
}
