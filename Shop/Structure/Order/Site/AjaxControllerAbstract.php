<?php

namespace Shop\Structure\Order\Site;

use Ideal\Core\AjaxController;
use Ideal\Core\View;
use Shop\Structure\Basket\Site\Model;
use Ideal\Core\Config;

class AjaxControllerAbstract extends AjaxController
{
    protected $user;

    protected $answer = [
        'error' => false,
        'text' => '',
    ];

    public function templateInit($tplName = ''): void
    {
        if (!stream_resolve_include_path($tplName)) {
            echo 'Нет файла шаблона ' . $tplName;
            exit;
        }

        $tplRoot = dirname(stream_resolve_include_path($tplName));
        $tplName = basename($tplName);

        // Определяем корневую папку системы для подключение шаблонов из любой вложенной папки через их путь
        $config = Config::getInstance();
        $cmsFolder = DOCUMENT_ROOT . '/' . $config->cmsFolder;

        $folders = [$tplRoot, $cmsFolder];
        $this->view = new View($folders, $config->cache['templateSite']);
        $this->view->loadTemplate($tplName);
    }

    public function setUserAction(): void
    {
        $user = [];
        foreach ($_POST as $k => $v) {
            if ((strpos($k, 'required') !== false) && ((string) $v === '')) {
                $this->answer['error'] = true;
                $this->answer['text'] = 'Заполните все поля отмечанные звездочкой';
                $this->answer['class'][] = $k;
            }

            $k = htmlspecialchars($k);
            $v = htmlspecialchars($v);
            $user[$k] = $v;
        }

        $this->answer['user'] = $user;
    }

    public function orderAction(): void
    {
        $this->setUserAction();
        if ($this->answer['error']) {
            return;
        }

        $this->user = $this->answer['user'];
        $this->answer['user'] = '';

        $fname = $this->user['billing_first_name_required'];
        $lname = $this->user['billing_last_name_required'];
        $address = $this->user['billing_address_required'];
        $email = $this->user['billing_email_required'];
        $phone = $this->user['billing_phone'];
        $payment = $this->user['payment_method'];
        $comment = $this->user['order_comments'];

        $basketModel = new Model('');
        $basket = $basketModel->parseCookie($_COOKIE['basket'] ?? null);
        $goods = $basketModel->getGoodsFromBasket($basket);

        if ($goods === []) {
            $this->answer['error'] = true;
            $this->answer['text'] = 'Вв не добавили товары в корзину';
            exit();
        }

        $config = Config::getInstance();

        $price = $basket['total'];

        $order = new \Shop\Structure\Order\Site\Model('');
        $idOrder = $order->createOrder($comment, $lname . ' ' . $fname, $address);
        $this->templateInit('Shop/Structure/Order/Site/letter.twig');

        $this->view->idOrder = $idOrder;
        $this->view->fname = $fname;
        $this->view->lname = $lname;
        $this->view->address = $address;
        $this->view->email = $email;
        $this->view->phone = $phone;
        $this->view->payment = $payment;
        $this->view->comment = $comment;
        $this->view->basket = $basket;
        $this->view->domain = $config->domain;

        $orderMail = $this->view->render();
        $order->updateOrder($orderMail, $idOrder, $price);

        $orderTitle = 'Заказ c ' . $config->domain;
        $headers = "From: {$config->robotEmail}\r\n"
            . 'Content-type: text/html; charset="utf-8"';

        if (mail($email, $orderTitle, $orderMail, $headers)
            && mail($config->mailForm, $orderTitle, $orderMail, $headers)
        ) {
            $this->answer['text'] = 'Ваш заказ принят в обработку. Наш менеджер скоро с Вами свяжется.';
        } else {
            $this->answer['text'] .= 'Ошибка. Попробуйте чуть позже';
            $this->answer['error'] = 1;
        }
    }

    public function basketAction(): void
    {
        $this->answer['text'] = 'Done!';
        $this->answer['js'] = 'deleteCookie("basket");';
        exit();
    }

    public function __destruct()
    {
        if (isset($this->answer['class'])) {
            $this->answer['class'] = '[name=' . implode('], [name=', $this->answer['class']) . ']';
        }

        print json_encode($this->answer);
        exit();
    }
}
