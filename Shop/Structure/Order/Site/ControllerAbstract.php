<?php
namespace Shop\Structure\Order\Site;

class ControllerAbstract extends \Ideal\Structure\Part\Site\Controller
{
    /** @var  Model model */
    protected $model;

    public function indexAction()
    {
        parent::indexAction();
        $this->view->goods = $this->model->getGoods();
    }
}
