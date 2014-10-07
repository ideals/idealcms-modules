<?php
namespace Shop\Structure\Order\Site;

use Ideal\Core\Config;
use Shop\Structure\Basket;

class AjaxControllerAbstract extends \Ideal\Core\AjaxController
{
    /**
     * Формирование заказа
     */
    public function orderAction()
    {
        $answer = array('error' => 0, 'text' => '');
        $config = Config::getInstance();
        /*
         * Данные из формы заказа
         */
        $email = @ htmlspecialchars($_POST['email']);
        $fio = @ htmlspecialchars($_POST['fio']);
        $phone = @ htmlspecialchars($_POST['phone']);
        $postcode = @ htmlspecialchars($_POST['postcode']);
        $city = @ htmlspecialchars($_POST['city']);
        $address = @ htmlspecialchars($_POST['address']);
        $comment = @ htmlspecialchars($_POST['comment']);
        // Установка цены за доставку
        switch ($_POST['delivery']) {
            default:
            case 1:
                $delivery = 'Курьерская доставка по Москве';
                $deliveryMoney = 300;
                $payMethod = 'Наличными курьеру';
                break;
            case 2:
                $delivery = 'Курьерская доставка по Московской области';
                $deliveryMoney = 650;
                $payMethod = 'Наличными курьеру';
                break;
            case 3:
                $delivery = 'Почта России';
                $deliveryMoney = 0;
                $payMethod = 'Безналичная оплата';
                break;
            case 4:
                $delivery = 'EMS - Почта России';
                $deliveryMoney = 0;
                $payMethod = 'Безналичная оплата';
                break;
        }

        /**
         * Сегмент кода нужен если подключен модуль Cabinet_User
         *
        session_start();
        if (!isset($_SESSION['userChecked']) || $_SESSION['userChecked'] != true) {
            $User = new \Cabinet\Structure\User\Site\Model('');
            $answer = $User->regOrder();
        }
        */

        if ($answer['error'] == 0) {
            $basketModel = new Basket\Site\Model('');
            $goods = $basketModel->getGoods(); // Товары из корзины
            $price = (float)$goods['price'] + $deliveryMoney;

            $order = new \Shop\Structure\Order\Site\Model('');
            $idOrder = $order->createOrder($comment, $fio, $city . ', ' . $address);

            /**
             * Генерация письма
             */
            $this->templateInit('Shop/Structure/Order/Site/letter.twig');
            $this->view->idOrder = $idOrder;
            $this->view->delivery = $delivery;
            $this->view->payMethod = $payMethod;
            $this->view->goods = $goods['good'];
            $this->view->deliveryMoney = $deliveryMoney;
            $this->view->price = $price;
            $this->view->fio = $fio;
            $this->view->phone = $phone;
            $this->view->postcode = $postcode;
            $this->view->city = $city;
            $this->view->address = $address;
            $this->view->comment = $comment;
            $this->view->email = $email;
            $this->view->domain = $config->domain;
            // Текст письма
            $orderMail = $this->view->render();

            $order->updateOrder($orderMail, $idOrder, $price);
            $orderTitle = 'Заказ c ' . $config->domain;
            $headers = "From: {$config->robotEmail}\r\n"
                . "Content-type: text/html; charset=\"utf-8\"";

            if (mail($email, $orderTitle, $orderMail, $headers) && mail($config->mailForm, $orderTitle, $orderMail, $headers)) {
                $answer['text'] = 'Ваш заказ принят в обработку. Наш менеджер скоро с Вами свяжется.';
            } else {
                $answer['text'] .= 'Ошибка. Попробуйте чуть позже';
                $answer['error'] = 1;
            }
        }
        print json_encode($answer);
        exit;
    }
}
