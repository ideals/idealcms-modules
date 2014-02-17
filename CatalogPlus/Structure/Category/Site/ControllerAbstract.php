<?php
namespace CatalogPlus\Structure\Category\Site;

use Ideal\Core\Request;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    /* @var $model Model Модель соответствующая этому контроллеру */
    protected $model;

    public function indexAction()
    {
        parent::indexAction();

        $request = new Request();
        $page = intval($request->page);

        if ($this->model->isNotIndex()) {
            $goods = $this->model->getGoods();
            $this->view->parts = $goods->getList($page);
            $this->view->pager = $goods->getPager('page');
        }
    }
}
