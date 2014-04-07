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

        $goodModel = new \CatalogPlus\Structure\Good\Site\Model('');
        $goodModel->setCategoryModel($this->model);
        $this->view->goods = $goodModel->getList($page);
        $this->view->pager = $goodModel->getPager('page');

        // TODO реализация вывода списка групп и подгруппы текущей активной группы
        $this->view->listCat = $this->model->getListCategory();

    }
}
