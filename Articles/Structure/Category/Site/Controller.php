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

        $this->view->parts = $this->model->getCategories();

        if (isset($_GET['page'])) {
            $page = intval($_GET['page']);
        } else {
            $page = 1;
        }

        $paper = $this->model->getArticles(15, $page);

        $this->view->list = $paper['list'];
        $this->view->goods = $paper['paper'];


        return;
    }
}