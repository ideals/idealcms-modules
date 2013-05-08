<?php
namespace Articles\Structure\Category\Site;

class Controller extends \Ideal\Structure\Part\Site\ControllerAbstract
{
    /* @var $model Model */
    protected $model;

    public function indexAction()
    {
        parent::indexAction();

        $this->view->url = $this->model->object['url'];

        $paper = $this->model->getList();
        array_unshift($paper, array("cap" => "Все статьи", "url" => "article", "url_full" => "/article"));
        $this->view->parts = $paper;

        $this->view->goods = $this->model->getArticles();
        return;
    }
}