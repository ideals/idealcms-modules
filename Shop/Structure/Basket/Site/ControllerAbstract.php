<?php
namespace Shop\Structure\Basket\Site;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    /** @var  Model model */
    protected $model;

    public function indexAction()
    {
        parent::indexAction();

        $this->view->goods = $this->model->getGoods();

        $basket = $_COOKIE['basket'];
        $basket = json_decode($basket, true);

        if (!isset($basket['good']) || !(count($basket['good']) > 0)) {
            $this->view->hideBasket = true;
        } else {
            $this->view->basket = $basket;
        }
    }
}
