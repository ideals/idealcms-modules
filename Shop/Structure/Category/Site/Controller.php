<?php
namespace Shop\Structure\Category\Site;

class Controller extends \Ideal\Structure\Part\Site\ControllerAbstract
{
    public function indexAction()
    {
        parent::indexAction();
        $this->view->goods = $this->model->getGoods();
    }
}