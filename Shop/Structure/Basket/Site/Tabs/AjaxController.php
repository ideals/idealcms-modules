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
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                // Если валидация пройдена успешно, то записываем значение в куки
                setcookie("order_comments", $form->getValue('order_comments'));
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
                );
                $delivery = json_encode($delivery, JSON_FORCE_OBJECT);
                setcookie("delivery", $delivery);
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
        $form->add('lastname', 'text');
        $form->add('name', 'text');
        $form->add('phone', 'text');
        $form->add('email', 'text');
        $form->setValidator('lastname', 'required');
        $form->setValidator('name', 'required');
        $form->setValidator('phone', 'required');
        $form->setValidator('email', 'required');
        $form->setValidator('email', 'email');
        $form->setValidator('phone', 'phone');
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                // Если валидация пройдена успешно, то записываем значение в куки
                $userInfo = array(
                    'lastname' => $form->getValue('lastname'),
                    'name' => $form->getValue('name'),
                    'phone' => $form->getValue('phone'),
                    'email' => $form->getValue('email'),
                );
                $userInfo = json_encode($userInfo, JSON_FORCE_OBJECT);
                setcookie("userInfo", $userInfo);
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
