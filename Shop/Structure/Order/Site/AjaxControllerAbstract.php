<?php
namespace Shop\Structure\Order\Site;

use Ideal\Core\Config;
use Shop\Structure\Basket;

class AjaxControllerAbstract extends \Ideal\Core\AjaxController
{
    protected $user;
    protected $answer;

    public function __construct()
    {
        $this->answer = array(
            'error' => false,
            'text' => ''
        );
    }

    public function __destruct()
    {
        if (isset($this->answer['class'])) {
            $this->answer['class'] = '[name=' . implode('], [name=', $this->answer['class']) . ']';
        }
        print json_encode($this->answer);
        exit();
    }

    public function templateInit($tplName = '')
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

        $folders = array_merge(array($tplRoot, $cmsFolder));
        $this->view = new \Ideal\Core\View($folders, $config->cache['templateSite']);
        $this->view->loadTemplate($tplName);
    }

    public function setUserAction()
    {
        $user = array();
        foreach ($_POST as $k => $v) {
            if ((strpos($k, 'required') !== false) && (strlen($v) == 0)) {
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

    public function orderAction()
    {
        $this->setUserAction();
        if (!$this->answer['error']) {
            $this->user = $this->answer['user'];
            $this->answer['user'] = '';

            $fname = $this->user['billing_first_name_required'];
            $lname = $this->user['billing_last_name_required'];
            $address = $this->user['billing_address_required'];
            $email = $this->user['billing_email_required'];
            $phone = $this->user['billing_phone'];
            $payment = $this->user['payment_method'];
            $comment = $this->user['order_comments'];

            $basket = new \Shop\Structure\Basket\Site\Model('');
            $basket = $basket->getGoods();
            if ($basket === false) {
                $this->answer['error'] = true;
                $this->answer['text'] = 'Данная услуга временно не работает. Поробуйте чуть позже';
                exit();
            }
            if (count($basket['goods']) === 0) {
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
                . "Content-type: text/html; charset=\"utf-8\"";

            if (mail($email, $orderTitle, $orderMail, $headers)
                && mail($config->mailForm, $orderTitle, $orderMail, $headers)
            ) {
                $this->answer['text'] = 'Ваш заказ принят в обработку. Наш менеджер скоро с Вами свяжется.';
            } else {
                $this->answer['text'] .= 'Ошибка. Попробуйте чуть позже';
                $this->answer['error'] = 1;
            }
        } else {
            // error code
        }

    }

    public function basketAction()
    {
        $this->answer['text'] = 'Done!';
        $this->answer['js'] = 'deleteCookie("basket");';
        exit();
    }
}
