<?php
namespace Shop\Structure\Category\Site;

class Controller extends \Ideal\Structure\Part\Site\ControllerAbstract
{
    /* @var $model Model */
    protected $model;

    public function indexAction()
    {
        parent::indexAction();
        $this->view->goods = $this->view->parts;
        unset($this->view->parts);
    }
}