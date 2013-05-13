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

        if (isset($_GET['page'])) {
            $page = intval($_GET['page']);
        } else {
            $page = 1;
        }

        $paper = $this->model->getArticles(1,$page);

        $this->view->list = $paper['list'];
        $this->view->goods = $paper['paper'];


        return;
    }
}