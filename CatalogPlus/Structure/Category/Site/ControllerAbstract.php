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
        if ($page == 0) $page = 1;
        $goods = $this->model->getGoods($page);
        $this->view->goods = $goods;
        // TODO реализация вывода списка групп и подгруппы текущей активной группы
        //$this->view->listCat = $this->model->getListCategory();
        $this->view->pager = $this->model->getPager('page');
    }
}
