<?php
namespace Shop\Structure\Basket\Site;

class Controller extends \Ideal\Core\Site\Controller
{
    public function indexAction()
    {
        parent::indexAction();

        $this->view->goods = $this->model->getGoods();

        $basket = $_COOKIE['basket'];
        $basket = json_decode($basket, true);
        $this->view->basket = $basket;
    }
}