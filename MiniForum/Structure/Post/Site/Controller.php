<?php
namespace MiniForum\Structure\Post\Site;


class Controller extends ControllerAbstract
{
    protected $model;

    public function indexAction()
    {
        $this->title = 'Форум';
        parent::indexAction();
    }

}