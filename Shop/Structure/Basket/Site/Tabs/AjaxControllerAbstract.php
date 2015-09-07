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
use Ideal\Core\Db;
use Ideal\Core\Config;

class AjaxControllerAbstract extends \Ideal\Core\AjaxController
{
    // Обрабатывает запросы для формы из шаблона поумолчанию "Подтверждение заказа"
    public function confirmationAction()
    {
        $request = new Request();
        $form = new Forms('confirmationForm');
        $form->setAjaxUrl('/');
        $form->setSuccessMessage(false);
        $form->setClearForm(false);
        $form->add('order_comments', 'text');
        $form->add('currentTabId', 'text');
        $form->add('currentTabName', 'text');
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                // Если валидация пройдена успешно, то записываем значение в куки
                $orderComments = array(
                    'order_comments' => array('label' => 'Заметки к заказу', 'value' => $form->getValue('order_comments')),
                    'tabName' => $form->getValue('currentTabName')
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
        $form->setClearForm(false);
        $form->add('billing_first_name_required', 'text');
        $form->add('billing_last_name_required', 'text');
        $form->add('billing_address_required', 'text');
        $form->add('billing_email_required', 'text');
        $form->add('billing_phone', 'text');
        $form->add('deliveryMethod', 'text');
        $form->add('currentTabId', 'text');
        $form->add('currentTabName', 'text');
        $form->setValidator('billing_first_name_required', 'required');
        $form->setValidator('billing_last_name_required', 'required');
        $form->setValidator('billing_address_required', 'required');
        $form->setValidator('billing_email_required', 'required');
        $form->setValidator('billing_email_required', 'email');
        $form->setValidator('billing_phone', 'phone');
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                // Если валидация пройдена успешно, то записываем значение в куки
                // Получаем название способа доставки
                switch ($form->getValue('deliveryMethod')) {
                    case '1':
                        $selectedValue = 'Курьерская доставка по Москве';
                        break;
                    case '2':
                        $selectedValue = 'Курьерская доставка по Московской области';
                        break;
                    case '3':
                        $selectedValue = 'Почта России';
                        break;
                    case '4':
                        $selectedValue = 'EMS - Почта России';
                        break;
                    default:
                        $selectedValue = '';
                        break;
                }

                $delivery = array(
                    'first_name' => array('label' => 'Имя', 'value' => $form->getValue('billing_first_name_required')),
                    'last_name' => array('label' => 'Фамилия', 'value' => $form->getValue('billing_last_name_required')),
                    'address' => array('label' => 'Адрес', 'value' => $form->getValue('billing_address_required')),
                    'email' => array('label' => 'Email-адрес', 'value' => $form->getValue('billing_email_required')),
                    'phone' => array('label' => 'Телефон', 'value' => $form->getValue('billing_phone')),
                    'deliveryMethod' => array('label' => 'Доставка', 'value' => $form->getValue('deliveryMethod'), 'selectedValue' => $selectedValue),
                    'tabName' => $form->getValue('currentTabName')
                );
                $tabID = 'tab_' . $form->getValue('currentTabId');
                if (isset($_COOKIE['basket'])) {
                    $basket = json_decode($_COOKIE['basket']);
                    $basket->tabsInfo->$tabID = $delivery;
                    if (!isset($basket->name)) {
                        $basket->name = $form->getValue('billing_first_name_required');
                    }
                    if (!isset($basket->email)) {
                        $basket->email = $form->getValue('billing_email_required');
                    }
                    $basket->address = $form->getValue('billing_address_required');
                } else {
                    $basket = (object) array(
                        'name' => $form->getValue('billing_first_name_required'),
                        'email' => $form->getValue('billing_email_required'),
                        'address' => $form->getValue('billing_address_required'),
                        'tabsInfo' => array($tabID => $delivery)
                    );
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
        $form->setClearForm(false);
        $form->add('userinfo_lastname', 'text');
        $form->add('userinfo_name', 'text');
        $form->add('userinfo_phone', 'text');
        $form->add('userinfo_email', 'text');
        $form->add('currentTabId', 'text');
        $form->add('currentTabName', 'text');
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
                    'userinfo_lastname' => array('label' => 'Фамилия', 'value' => $form->getValue('userinfo_lastname')),
                    'userinfo_name' =>  array('label' => 'Имя', 'value' => $form->getValue('userinfo_name')),
                    'userinfo_phone' =>  array('label' => 'Телефон', 'value' => $form->getValue('userinfo_phone')),
                    'userinfo_email' =>  array('label' => 'E-mail', 'value' => $form->getValue('userinfo_email')),
                    'tabName' => $form->getValue('currentTabName')
                );
                $tabID = 'tab_' . $form->getValue('currentTabId');
                if (isset($_COOKIE['basket'])) {
                    $basket = json_decode($_COOKIE['basket']);
                    $basket->tabsInfo->$tabID = $userInfo;
                    $basket->name = $form->getValue('userinfo_name');
                    $basket->email = $form->getValue('userinfo_email');
                } else {
                    $basket = (object) array(
                        'name' => $form->getValue('userinfo_name'),
                        'email' => $form->getValue('userinfo_email'),
                        'tabsInfo' => array($tabID => $userInfo)
                    );
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
        $form->setClearForm(false);
        $form->add('payment_method', 'text');
        $form->add('currentTabId', 'text');
        $form->add('currentTabName', 'text');
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                // Если валидация пройдена успешно, то записываем значение в куки
                // Получаем название способа оплаты
                switch ($form->getValue('payment_method')) {
                    case 'cod':
                        $selectedValue = 'Наличными';
                        break;
                    case 'bacs':
                        $selectedValue = 'Прямой банковский перевод';
                        break;
                    case 'cheque':
                        $selectedValue = 'Оплата чеками';
                        break;
                    case 'paypal':
                        $selectedValue = 'PayPal';
                        break;
                    default:
                        $selectedValue = '';
                        break;
                }
                $payment = array(
                    'payment_method' => array('label' => 'Способ оплаты', 'value' => $form->getValue('payment_method'), 'selectedValue' => $selectedValue),
                    'tabName' => $form->getValue('currentTabName')
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
        $config = Config::getInstance();
        $form = new Forms('finishForm');
        $form->setAjaxUrl('/');
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                // Если валидация пройдена успешно, то отрабатываем финальную часть
                $basket = json_decode($_COOKIE['basket']);
                $price = $basket->total;
                $message = '<h2>Товары</h2><br />';
                $message .= '<table>';
                $message .= '<tr><th>Наименование</th><th>Цена</th><th>Количество</th><th>Сумма</th></tr>';

                // Собираем итнформацию о заказанных товарах
                foreach ($basket->goods as $good) {
                    $summ = intval($good->count) * (intval($good->sale_price));
                    $message .= '<tr><td>' . $good->name . '</td><td>' . intval($good->sale_price) . '</td><td>' . $good->count . '</td><td>' . $summ . '</td></tr>';
                }
                $message .= '<tr><td colspan="3"></td><td>Общая сумма заказа: ' . $price . '</td></tr>';
                $message .= '</table>';

                foreach ($basket->tabsInfo as $tabInfo) {
                    $message .= '<br /><br /><h2>' . $tabInfo->tabName . '</h2><br />';
                    unset($tabInfo->tabName);
                    foreach ($tabInfo as $key => $field) {
                        if (isset($field->label)) {
                            $label = $field->label;
                        } else {
                            $label = $key;
                        }
                        $value = '';
                        if (isset($field->selectedValue)) {
                            $value = $field->selectedValue;
                        } elseif (isset($field->value)) {
                            $value = $field->value;
                        }
                        $message .= $label . ': ' . $value . '<br />';
                    }

                }

                // Сохраняем информацию о заказе в справочник "Заказы с сайта"
                if (!empty($basket->name) && !empty($basket->email)) {
                    // Отправляем сообщение покупателю
                    $topic = 'Заказ в магазине "' . $config->domain . '"';
                    $form->sendMail($config->robotEmail, $basket->email, $topic, $message, true);

                    // Отправляем сообщение менеджеру
                    $topic = 'Заказ в магазине "' . $config->domain . '"';
                    $referer = $form->getValue('referer');

                    if ($referer == 'null') { // Отлавливаем прямой переход
                        $referer = 'Прямой переход';
                    } elseif (strripos($referer, 'yandex') !== false) { // Отлавливаем яндекс
                        $referer = 'Яндекс';
                    } elseif (strripos($referer, 'google') !== false) { // Отлавливаем гугл
                        $referer = 'Google';
                    } else { // Отлавливаем другие сайты
                        $referer = 'Другой сайт';
                    }

                    $message .= '<br />Источник перехода: ' . $referer;
                    $form->sendMail($config->robotEmail, $config->mailForm, $topic, $message, true);

                    $form->saveOrder($basket->name, $basket->email, $message, $basket->total * 100);
                }

                $this->finishOrder();
                echo 'Ваш заказ принят';
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

    // Запускает ряд методов для завершения работы над заказом
    public function finishOrder()
    {
        $this->saveOrderInShopSructure();
        $this->clearBasket();
    }

    // Очищает информацию о корзине
    public function clearBasket()
    {
        setcookie("basket", '', time() - 3600);
    }

    // Сохраняет информацию о заказе в структуре "Order" модуля "Shop"
    public function saveOrderInShopSructure()
    {
        $response = false;
        $config = Config::getInstance();
        $prefix = $config->db['prefix'];
        if (class_exists('\Shop\Structure\Order\Site\Model')) {
            $db = Db::getInstance();
            $sql = "SELECT ID as last_id FROM {$prefix}shop_structure_order ORDER BY ID DESC LIMIT 1";
            $lastId = $db->select($sql);
            $orderNumber = $lastId[0]['last_id'] + 1;
            $response = $orderNumber;

            // Получаем идентификатор справочника "Заказы в магазине" для построения поля "prev_structure"
            $dataList = $config->getStructureByName('Ideal_DataList');
            $prevStructure = $dataList['ID'] . '-';
            $par = array('structure' => 'Shop_Order');
            $fields = array('table' => $config->db['prefix'] . 'ideal_structure_datalist');
            $row = $db->select('SELECT ID FROM &table WHERE structure = :structure', $par, $fields);
            $prevStructure .= $row[0]['ID'];

            $basket = json_decode($_COOKIE['basket']);


            $message = '<h2>Товары</h2><br />';
            $message .= '<table>';
            $message .= '<tr><th>Наименование</th><th>Цена</th><th>Количество</th><th>Сумма</th></tr>';
            $Id1c = array();

            // Собираем итнформацию о заказанных товарах
            foreach ($basket->goods as $key => $good) {
                $goodId = explode('_', $key);
                $offerId = $goodId[1];
                $goodId = $goodId[0];
                $par = array('ID' => $offerId);
                $fields = array('table' => $config->db['prefix'] . 'catalogplus_structure_offer');
                $row = $db->select('SELECT offer_id, good_id FROM &table WHERE ID = :ID LIMIT 1', $par, $fields);
                $Id1c[] = $row[0]['good_id'];

                $summ = intval($good->count) * (intval($good->sale_price));
                $message .= '<tr><td>' . $good->name . '</td><td>' . intval($good->sale_price) . '</td><td>' . $good->count . '</td><td>' . $summ . '</td></tr>';

                $prevOrder = $config->getStructureByName('Shop_Order');
                $insert = array();
                $insert['prev_structure'] = $prevOrder['ID'] . '-' . $response;
                $insert['order_id'] = $response;
                $insert['good_id_1c'] = $row[0]['good_id'];
                $insert['offer_id_1c'] = $row[0]['offer_id'];
                $insert['count'] = intval($good->count);
                $insert['sum'] = $summ * 100;

                $db->insert(
                    $prefix . 'shop_structure_orderdetail',
                    $insert
                );
            }
            $message .= '<tr><td colspan="3"></td><td>Общая сумма заказа: ' . intval($basket->total) . '</td></tr>';
            $message .= '</table>';

            $address = '';
            if (isset($basket->address)) {
                $address = $basket->address;
            }
            // Генерируем сообщение
            foreach ($basket->tabsInfo as $tabInfo) {
                $message .= '<br /><br /><h2>' . $tabInfo->tabName . '</h2><br />';
                unset($tabInfo->tabName);
                foreach ($tabInfo as $key => $field) {
                    if (isset($field->label)) {
                        $label = $field->label;
                    } else {
                        $label = $key;
                    }
                    $value = '';
                    if (isset($field->selectedValue)) {
                        $value = $field->selectedValue;
                    } elseif (isset($field->value)) {
                        $value = $field->value;
                    }
                    $message .= $label . ': ' . $value . '<br />';
                }
            }

            // Получаем идентификатор пользователя
            $userId = 0;
            if (isset($_SESSION['login']['is_active']) && $_SESSION['login']['is_active']) {
                $userId = intval($_SESSION['login']['ID']);
            }

            // Записываем данные
            $db->insert(
                $prefix . 'shop_structure_order',
                array(
                    'prev_structure' => $prevStructure,
                    'name' => 'Заказ № ' . $orderNumber,
                    'url' => 'zakaz-N-' . $orderNumber,
                    'price' => $basket->total * 100,
                    'stock' => $basket->count,
                    'address' => $address,
                    'date_create' => time(),
                    'date_mod' => time(),
                    'content' => $message,
                    'is_active' => 1,
                    'goods_id'  => implode(',', $Id1c),
                    'structure' => 'Shop_OrderDetail',
                    'user_id' => $userId
                )
            );
        }
        return $response;
    }
}
