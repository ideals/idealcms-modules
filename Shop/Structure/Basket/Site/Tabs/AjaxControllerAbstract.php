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

    // Таблицы с товарами и предложениями в конструкторе
    // TODO определять автоматически модуль из которых надо брать, сейчас прописано CatalogPlus
    protected $tableGood;
    protected $tableOffer;

    protected $orderId = false;

    /** @var array Дополнительные HTTP-заголовки ответа  */
    public $httpHeaders = array();

    /**
     * Генерация данных и установка значений по умолчанию
     */
    public function __construct()
    {
        // Указываем таблицы где хранятся данные
        $config = Config::getInstance();
        $prefix = $config->db['prefix'];
        $this->tableGood = $prefix . 'catalogplus_structure_good';
        $this->tableOffer = $prefix . 'catalogplus_structure_offer';

    }

    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

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
                    'order_comments' => array(
                        'label' => 'Заметки к заказу',
                        'value' => $form->getValue('order_comments')
                    ),
                    'tabName' => $form->getValue('currentTabName')
                );
                $tabID = 'tab_' . $form->getValue('currentTabId');
                if (isset($_COOKIE['tabsInfo'])) {
                    $tabsInfo = json_decode($_COOKIE['tabsInfo']);
                    $tabsInfo->$tabID = $orderComments;
                } else {
                    $tabsInfo = (object) array($tabID => $orderComments);
                }
                setcookie("tabsInfo", json_encode($tabsInfo));
            } else {
                return 'stopValidationError';
            }
        } else {
            // Обработка запросов для получения функциональных частей формы
            $text = '';
            switch ($request->target) {
                // Генерируем js
                case 'js':
                    $script = $this->getStartJsForStep('confirmationForm');
                    $form->setJs($script);
                    $this->httpHeaders['Content-type'] = 'application/javascript';
                    $request->mode = 'js';
                    break;
                // Генерируем css
                case 'css':
                    $this->httpHeaders['Content-type'] = 'text/css';
                    $request->mode = 'css';
                    break;
                // Генерируем стартовую часть формы
                case 'start':
                    ob_start();
                    $form->start();
                    $text = ob_get_contents();
                    ob_end_clean();
                    break;
            }
            if (empty($text)) {
                ob_start();
            $form->render();
                $text = ob_get_contents();
                ob_end_clean();
        }
            return $text;
        }
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
                    'last_name' => array(
                        'label' => 'Фамилия',
                        'value' => $form->getValue('billing_last_name_required')
                    ),
                    'address' => array('label' => 'Адрес', 'value' => $form->getValue('billing_address_required')),
                    'email' => array('label' => 'Email-адрес', 'value' => $form->getValue('billing_email_required')),
                    'phone' => array('label' => 'Телефон', 'value' => $form->getValue('billing_phone')),
                    'deliveryMethod' => array(
                        'label' => 'Доставка',
                        'value' => $form->getValue('deliveryMethod'),
                        'selectedValue' => $selectedValue
                    ),
                    'tabName' => $form->getValue('currentTabName')
                );
                $tabID = 'tab_' . $form->getValue('currentTabId');
                if (isset($_COOKIE['tabsInfo'])) {
                    $tabsInfo = json_decode($_COOKIE['tabsInfo']);
                    if (!isset($tabsInfo->generalInfo->name)) {
                        $tabsInfo->generalInfo->name = $form->getValue('billing_first_name_required');
                    }
                    if (!isset($tabsInfo->generalInfo->email)) {
                        $tabsInfo->generalInfo->email = $form->getValue('billing_email_required');
                    }
                    $tabsInfo->generalInfo->address = $form->getValue('billing_address_required');
                    $tabsInfo->$tabID = $delivery;
                } else {
                    $tabsInfo = (object) array(
                        'generalInfo' => array(
                            'name' => $form->getValue('billing_first_name_required'),
                            'email' => $form->getValue('billing_email_required'),
                            'address' => $form->getValue('billing_address_required'),
                        ),
                        $tabID => $delivery
                    );
                }
                setcookie("tabsInfo", json_encode($tabsInfo));
            } else {
                return 'stopValidationError';
            }
        } else {
            // Обработка запросов для получения функциональных частей формы
            $text = '';
            switch ($request->target) {
                // Генерируем js
                case 'js':
                    $script = $this->getStartJsForStep('deliveryForm');
                    $form->setJs($script);
                    $this->httpHeaders['Content-type'] = 'application/javascript';
                    $request->mode = 'js';
                    break;
                // Генерируем css
                case 'css':
                    $this->httpHeaders['Content-type'] = 'text/css';
                    $request->mode = 'css';
                    break;
                // Генерируем стартовую часть формы
                case 'start':
                    ob_start();
                    $form->start();
                    $text = ob_get_contents();
                    ob_end_clean();
                    break;
            }
            if (empty($text)) {
                ob_start();
            $form->render();
                $text = ob_get_contents();
                ob_end_clean();
        }
            return $text;
        }
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
                    'userinfo_name' => array('label' => 'Имя', 'value' => $form->getValue('userinfo_name')),
                    'userinfo_phone' => array('label' => 'Телефон', 'value' => $form->getValue('userinfo_phone')),
                    'userinfo_email' => array('label' => 'E-mail', 'value' => $form->getValue('userinfo_email')),
                    'tabName' => $form->getValue('currentTabName')
                );
                $tabID = 'tab_' . $form->getValue('currentTabId');
                if (isset($_COOKIE['tabsInfo'])) {
                    $tabsInfo = json_decode($_COOKIE['tabsInfo']);
                    $tabsInfo->$tabID = $userInfo;
                    $tabsInfo->generalInfo->name = $form->getValue('userinfo_name');
                    $tabsInfo->generalInfo->email = $form->getValue('userinfo_email');
                } else {
                    $tabsInfo = (object) array(
                        'generalInfo' => array(
                            'name' => $form->getValue('userinfo_name'),
                            'email' => $form->getValue('userinfo_email'),
                        ),
                        $tabID => $userInfo
                    );
                }
                setcookie("tabsInfo", json_encode($tabsInfo));
            } else {
                return 'stopValidationError';
            }
        } else {
            // Обработка запросов для получения функциональных частей формы
            $text = '';
            switch ($request->target) {
                // Генерируем js
                case 'js':
                    $script = $this->getStartJsForStep('authorizationForm');
                    $form->setJs($script);
                    $this->httpHeaders['Content-type'] = 'application/javascript';
                    $request->mode = 'js';
                    break;
                // Генерируем css
                case 'css':
                    $this->httpHeaders['Content-type'] = 'text/css';
                    $request->mode = 'css';
                    break;
                // Генерируем стартовую часть формы
                case 'start':
                    ob_start();
                    $form->start();
                    $text = ob_get_contents();
                    ob_end_clean();
                    break;
            }
            if (empty($text)) {
                ob_start();
            $form->render();
                $text = ob_get_contents();
                ob_end_clean();
        }
            return $text;
        }
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
                    'payment_method' => array(
                        'label' => 'Способ оплаты',
                        'value' => $form->getValue('payment_method'),
                        'selectedValue' => $selectedValue
                    ),
                    'tabName' => $form->getValue('currentTabName')
                );
                $tabID = 'tab_' . $form->getValue('currentTabId');
                if (isset($_COOKIE['tabsInfo'])) {
                    $tabsInfo = json_decode($_COOKIE['tabsInfo']);
                    $tabsInfo->tabsInfo->$tabID = $payment;
                } else {
                    $tabsInfo = (object) array($tabID => $payment);
                }
                setcookie("tabsInfo", json_encode($tabsInfo));
            } else {
                return 'stopValidationError';
            }
        } else {
            // Обработка запросов для получения функциональных частей формы
            $text = '';
            switch ($request->target) {
                // Генерируем js
                case 'js':
                    $script = $this->getStartJsForStep('paymentForm');
                    $form->setJs($script);
                    $this->httpHeaders['Content-type'] = 'application/javascript';
                    $request->mode = 'js';
                    break;
                // Генерируем css
                case 'css':
                    $this->httpHeaders['Content-type'] = 'text/css';
                    $request->mode = 'css';
                    break;
                // Генерируем стартовую часть формы
                case 'start':
                    ob_start();
                    $form->start();
                    $text = ob_get_contents();
                    ob_end_clean();
                    break;
            }
            if (empty($text)) {
                ob_start();
            $form->render();
                $text = ob_get_contents();
                ob_end_clean();
        }
            return $text;
        }
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
                $tabsInfo = json_decode($_COOKIE['tabsInfo']);
                $price = $basket->total;
                $message = '<h2>Товары</h2><br />';
                $message .= '<table>';
                $message .= '<tr><th>Наименование</th><th>Цена</th><th>Количество</th><th>Сумма</th></tr>';

                // Собираем информацию о заказанных товарах
                foreach ($basket->goods as $key => $good) {
                    $summ = intval($good->count) * (intval($good->sale_price));
                    $goodsItemId = explode('_', $key);
                    if (count($goodsItemId) > 1) {
                        $name = $this->getGoodName($goodsItemId[0], $goodsItemId[1]);
                    } else {
                        $name = $this->getGoodName($goodsItemId[0]);
                    }
                    $message .= '<tr><td>' . $name . '</td><td>' . intval($good->sale_price) . '</td><td>' . $good->count . '</td><td>' . $summ . '</td></tr>';
                }
                $message .= '<tr><td colspan="3"></td><td>Общая сумма заказа: ' . $price . '</td></tr>';
                $message .= '</table>';

                if (!empty($tabsInfo)) {
                    foreach ($tabsInfo as $key => $tabInfo) {
                        if ($key != 'generalInfo') {
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
                    }
                }

                // Пробуем сохранить информацию о заказе в структуре "Order" модуля "Shop"
                $this->saveOrderInShopSructure();

                // Сохраняем информацию о заказе в справочник "Заказы с сайта"
                if (!empty($tabsInfo->generalInfo->name) && !empty($tabsInfo->generalInfo->email)) {
                    // Отправляем сообщение покупателю
                    $orderIdPartTopic = $this->orderId ? ' № ' . $this->orderId : '';
                    $topic = 'Заказ в магазине "' . $config->domain . '"' . $orderIdPartTopic;
                    $form->sendMail($config->robotEmail, $tabsInfo->generalInfo->email, $topic, $message, true);

                    // Отправляем сообщение менеджеру
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

                    $form->saveOrder(
                        $tabsInfo->generalInfo->name,
                        $tabsInfo->generalInfo->email,
                        $message,
                        $basket->total * 100
                    );
                }

                $this->finishOrder();
                return 'Ваш заказ принят';
            } else {
                return 'stopValidationError';
            }
        } else {
            // Обработка запросов для получения функциональных частей формы
            $text = '';
            switch ($request->target) {
                // Генерируем js
                case 'js':
                    $script = $this->getStartJsForStep('finishForm');
                    $form->setJs($script);
                    $this->httpHeaders['Content-type'] = 'application/javascript';
                    $request->mode = 'js';
                    break;
                // Генерируем css
                case 'css':
                    $this->httpHeaders['Content-type'] = 'text/css';
                    $request->mode = 'css';
                    break;
                // Генерируем стартовую часть формы
                case 'start':
                    ob_start();
                    $form->start();
                    $text = ob_get_contents();
                    ob_end_clean();
                    break;
            }
            if (empty($text)) {
                ob_start();
            $form->render();
                $text = ob_get_contents();
                ob_end_clean();
        }
            return $text;
        }
    }

    // Запускает ряд методов для завершения работы над заказом
    public function finishOrder()
    {
        $this->clearBasket();
    }

    // Очищает информацию о корзине
    public function clearBasket()
    {
        setcookie("basket", null, -1, '/');
        setcookie("tabsInfo", null, -1, '/');
    }

    /**
     * Сохраняет информацию о заказе в структуре "Order" модуля "Shop"
     */
    public function saveOrderInShopSructure()
    {
        $config = Config::getInstance();
        $prefix = $config->db['prefix'];
        if (class_exists('\Shop\Structure\Order\Site\Model')) {
            $db = Db::getInstance();
            $nextId = $db->select("SHOW TABLE STATUS WHERE name='{$prefix}shop_structure_order'");
            $orderNumber = $nextId[0]['Auto_increment'];
            $this->setOrderId($orderNumber);

            // Получаем идентификатор справочника "Заказы в магазине" для построения поля "prev_structure"
            $dataList = $config->getStructureByName('Ideal_DataList');
            $prevStructure = $dataList['ID'] . '-';
            $par = array('structure' => 'Shop_Order');
            $fields = array('table' => $config->db['prefix'] . 'ideal_structure_datalist');
            $row = $db->select('SELECT ID FROM &table WHERE structure = :structure', $par, $fields);
            $prevStructure .= $row[0]['ID'];

            $basket = json_decode($_COOKIE['basket']);
            $tabsInfo = json_decode($_COOKIE['tabsInfo']);


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
                $goodsItemId = explode('_', $key);
                if (count($goodsItemId) > 1) {
                    $name = $this->getGoodName($goodsItemId[0], $goodsItemId[1]);
                } else {
                    $name = $this->getGoodName($goodsItemId[0]);
                }
                $message .= '<tr><td>' . $name . '</td><td>' . intval($good->sale_price) . '</td><td>' . $good->count . '</td><td>' . $summ . '</td></tr>';

                $prevOrder = $config->getStructureByName('Shop_Order');
                $insert = array();
                $insert['prev_structure'] = $prevOrder['ID'] . '-' . $this->orderId;
                $insert['order_id'] = $this->orderId;
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
            if (isset($tabsInfo->generalInfo->address)) {
                $address = $tabsInfo->generalInfo->address;
            }
            // Генерируем сообщение
            foreach ($tabsInfo as $key => $tabInfo) {
                if ($key != 'generalInfo') {
                    $message .= '<br /><h2>' . $tabInfo->tabName . '</h2><br />';
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
                    'goods_id' => implode(',', $Id1c),
                    'export' => 1,
                    'structure' => 'Shop_OrderDetail',
                    'user_id' => $userId
                )
            );
        }
    }

    /**
     * Получение наименования товара(предложения)
     *
     * @param $id Идентификатор торгового предложения
     * @param mixed $offer false если рассматривается товар, идентификатор торгового предложения в противном случае
     * @return string Пустая строка если нет товара(предложения), наименование в противном случае.
     */
    protected function getGoodName($id, $offer = false)
    {
        $db = Db::getInstance();
        if ($offer === false) {
            $sql = "SELECT e.name
                    FROM {$this->tableGood} AS e
                    WHERE ID = {$id} AND e.is_active = 1
                    LIMIT 1";
        } else {
            $sql = "SELECT o.name
                    FROM {$this->tableOffer} AS o
                    INNER JOIN {$this->tableGood} as g ON (g.ID = {$id})
                    WHERE o.ID = {$offer} AND o.is_active = 1
                    LIMIT 1";
        }
        $name = $db->select($sql);
        if (count($name) === 0) {
            return '';
        }
        return $name[0]['name'];
    }

    /**
     * Получаем начальный js, одинковый для каждого шага
     *
     * @param string $formIdValue Значение идентификатора формы
     * @return string js скрипт нужный для каждого этапа оформления корзины
     */
    protected function getStartJsForStep($formIdValue = '')
    {
        $script = '';
        if (!empty($formIdValue)) {
            $script .= <<<JS
                    if (typeof window.checkform != 'undefined') {
                        window.checkform.push('#{$formIdValue}');
                    } else {
                        window.checkform = ['#{$formIdValue}'];
                    }

                    $('#{$formIdValue}').on('form.successSend', function (event, result) {
                        function {$formIdValue}ServerValidationCheck() {
                            window.stopForm = 1;
                            if (result == 'stopValidationError') {
                            alert('Форма заполнена неправильно');
                                return;
                            }
                            window.stopForm = 0;
                        }
                        {$formIdValue}ServerValidationCheck();
                    });
JS;
        }
        return $script;
    }

    /**
     * Переопределяет HTTP-заголовки ответа
     *
     * @return array Массив где ключи - названия заголовков, а значения - содержание заголовков
     */
    public function getHttpHeaders()
    {
        return $this->httpHeaders;
    }
}
