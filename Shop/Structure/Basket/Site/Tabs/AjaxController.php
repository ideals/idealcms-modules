<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Shop\Structure\Basket\Site\Tabs;

use FormPhp\Forms;
use Ideal\Core\Request;

class AjaxController extends \Ideal\Core\AjaxController
{
    // Обрабатывает запросы для формы из шаблона поумолчанию "Подтверждение заказа"
    public function confirmationAction()
    {
        $request = new Request();
        $form = new Forms('confirmationForm');
        $form->setAjaxUrl('/');
        $form->setSuccessMessage(false);
        $form->add('order_comments', 'text');
        $form->add('currentTabId', 'text');
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                // Если валидация пройдена успешно, то записываем значение в куки
                $orderComments = array(
                    'order_comments' => $form->getValue('order_comments'),
                    'tabAppointment' => 'confirmation'
                );
                $tabID = 'tab_' . $form->getValue('currentTabId');
                if (isset($_COOKIE['basket'])) {
                    $basket = json_decode($_COOKIE['basket']);
                    $basket->tabsInfo->$tabID = $orderComments;
        } else {
                    $basket = (object) array('tabsInfo' => array($tabID => $orderComments));
                }
                setcookie("basket", json_encode($basket));
            }
        } else {
            // Обработка запросов для получения функциональных частей формы
            switch ($request->target) {
                // Генерируем js
                case 'js':
                    $script = <<<JS
                    if (typeof window.checkform != 'undefined') {
                        window.checkform.push('#confirmationForm');
                        } else {
                        window.checkform = ['#confirmationForm'];
                        }
JS;
                    $form->setJs($script);
                    $request->mode = 'js';
                    break;
                // Генерируем css
                case 'css':
                    $request->mode = 'css';
                    break;
                // Генерируем стартовую часть формы
                case 'start':
                    echo $form->start();
                    exit();
                    break;
            }
            $form->render();
        }
        exit();
    }

    // Обрабатывает запросы для формы из шаблона поумолчанию "Доставка(адрес) и способ досставки"
    public function deliveryAction()
    {
        $request = new Request();
        $form = new Forms('deliveryForm');
        $form->setAjaxUrl('/');
        $form->setSuccessMessage(false);
        $form->add('billing_first_name_required', 'text');
        $form->add('billing_last_name_required', 'text');
        $form->add('billing_address_required', 'text');
        $form->add('billing_email_required', 'text');
        $form->add('billing_phone', 'text');
        $form->add('deliveryMethod', 'text');
        $form->add('currentTabId', 'text');
        $form->setValidator('billing_first_name_required', 'required');
        $form->setValidator('billing_last_name_required', 'required');
        $form->setValidator('billing_address_required', 'required');
        $form->setValidator('billing_email_required', 'required');
        $form->setValidator('billing_email_required', 'email');
        $form->setValidator('billing_phone', 'phone');
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                // Если валидация пройдена успешно, то записываем значение в куки
                $delivery = array(
                    'first_name' => $form->getValue('billing_first_name_required'),
                    'last_name' => $form->getValue('billing_last_name_required'),
                    'address' => $form->getValue('billing_address_required'),
                    'email' => $form->getValue('billing_email_required'),
                    'phone' => $form->getValue('billing_phone'),
                    'deliveryMethod' => $form->getValue('deliveryMethod'),
                    'tabAppointment' => 'delivery'
                );
                $tabID = 'tab_' . $form->getValue('currentTabId');
                if (isset($_COOKIE['basket'])) {
                    $basket = json_decode($_COOKIE['basket']);
                    $basket->tabsInfo->$tabID = $delivery;
        } else {
                    $basket = (object) array('tabsInfo' => array($tabID => $delivery));
                }
                setcookie("basket", json_encode($basket));
            }
        } else {
            // Обработка запросов для получения функциональных частей формы
            switch ($request->target) {
                // Генерируем js
                case 'js':
                    $script = <<<JS
                    if (typeof window.checkform != 'undefined') {
                        window.checkform.push('#deliveryForm');
                    } else {
                        window.checkform = ['#deliveryForm'];
                    }
JS;
                    $form->setJs($script);
                    $request->mode = 'js';
                    break;
                // Генерируем css
                case 'css':
                    $request->mode = 'css';
                    break;
                // Генерируем стартовую часть формы
                case 'start':
                    echo $form->start();
                    exit();
                    break;
            }
            $form->render();
        }
        exit();
    }

    // Обрабатывает запросы для формы из шаблона поумолчанию "Авторизация"
    public function authorizationAction()
    {
        $request = new Request();
        $form = new Forms('authorizationForm');
        $form->setAjaxUrl('/');
        $form->setSuccessMessage(false);
        $form->add('userinfo_lastname', 'text');
        $form->add('userinfo_name', 'text');
        $form->add('userinfo_phone', 'text');
        $form->add('userinfo_email', 'text');
        $form->add('currentTabId', 'text');
        $form->setValidator('userinfo_lastname', 'required');
        $form->setValidator('userinfo_name', 'required');
        $form->setValidator('userinfo_phone', 'required');
        $form->setValidator('userinfo_email', 'required');
        $form->setValidator('userinfo_email', 'email');
        $form->setValidator('userinfo_phone', 'phone');
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                // Если валидация пройдена успешно, то записываем значение в куки
                $userInfo = array(
                    'userinfo_lastname' => $form->getValue('userinfo_lastname'),
                    'userinfo_name' => $form->getValue('userinfo_name'),
                    'userinfo_phone' => $form->getValue('userinfo_phone'),
                    'userinfo_email' => $form->getValue('userinfo_email'),
                    'tabAppointment' => 'authorization'
                );
                $tabID = 'tab_' . $form->getValue('currentTabId');
                if (isset($_COOKIE['basket'])) {
                    $basket = json_decode($_COOKIE['basket']);
                    $basket->tabsInfo->$tabID = $userInfo;
        } else {
                    $basket = (object) array('tabsInfo' => array($tabID => $userInfo));
                }
                setcookie("basket", json_encode($basket));
            }
        } else {
            // Обработка запросов для получения функциональных частей формы
            switch ($request->target) {
                // Генерируем js
                case 'js':
                    $script = <<<JS
                    if (typeof window.checkform != 'undefined') {
                        window.checkform.push('#authorizationForm');
                    } else {
                        window.checkform = ['#authorizationForm'];
                    }
JS;
                    $form->setJs($script);
                    $request->mode = 'js';
                    break;
                // Генерируем css
                case 'css':
                    $request->mode = 'css';
                    break;
                // Генерируем стартовую часть формы
                case 'start':
                    echo $form->start();
                    exit();
                    break;
            }
            $form->render();
        }
        exit();
    }

    // Обрабатывает запросы для формы из шаблона поумолчанию "Оплата и способ оплаты"
    public function paymentAction()
    {
        $request = new Request();
        $form = new Forms('paymentForm');
        $form->setAjaxUrl('/');
        $form->setSuccessMessage(false);
        $form->add('payment_method', 'text');
        $form->add('currentTabId', 'text');
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                // Если валидация пройдена успешно, то записываем значение в куки
                $payment = array(
                    'payment_method' => $form->getValue('payment_method'),
                    'tabAppointment' => 'payment'
                );
                $tabID = 'tab_' . $form->getValue('currentTabId');
                if (isset($_COOKIE['basket'])) {
                    $basket = json_decode($_COOKIE['basket']);
                    $basket->tabsInfo->$tabID = $payment;
                } else {
                    $basket = (object) array('tabsInfo' => array($tabID => $payment));
                }
                setcookie("basket", json_encode($basket));
            }
        } else {
            // Обработка запросов для получения функциональных частей формы
            switch ($request->target) {
                // Генерируем js
                case 'js':
                    $script = <<<JS
                    if (typeof window.checkform != 'undefined') {
                        window.checkform.push('#paymentForm');
                    } else {
                        window.checkform = ['#paymentForm'];
                    }
JS;
                    $form->setJs($script);
                    $request->mode = 'js';
                    break;
                // Генерируем css
                case 'css':
                    $request->mode = 'css';
                    break;
                // Генерируем стартовую часть формы
                case 'start':
                    echo $form->start();
                    exit();
                    break;
            }
            $form->render();
        }
        exit();
    }

    // Обрабатывает запросы для завершающей формы
    public function finishAction()
    {
        $request = new Request();
        $form = new Forms('finishForm');
        $form->setAjaxUrl('/');
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                // Если валидация пройдена успешно, то отправляем заказ менеджерам
            }
        } else {
            // Обработка запросов для получения функциональных частей формы
            switch ($request->target) {
                // Генерируем js
                case 'js':
                    $script = <<<JS
                    if (typeof window.checkform != 'undefined') {
                        window.checkform.push('#finishForm');
                    } else {
                        window.checkform = ['#finishForm'];
                    }
JS;
                    $form->setJs($script);
                    $request->mode = 'js';
                    break;
                // Генерируем css
                case 'css':
                    $request->mode = 'css';
                    break;
                // Генерируем стартовую часть формы
                case 'start':
                    echo $form->start();
                    exit();
                    break;
            }
            $form->render();
        }
        exit();
    }
}
