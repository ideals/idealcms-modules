<?php
namespace CatalogPlus\Structure\Category\Site;

use Ideal\Core\Request;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    /* @var $model Model Модель соответствующая этому контроллеру */
    protected $model;
    /** @var $goodModel \CatalogPlus\Structure\Good\Site\Model Модель товаров */
    protected $goodModel;

    public function indexAction()
    {
        parent::indexAction();

        $request = new Request();
        $page = intval($request->page);
        if ($page == 0) $page = 1;

        $this->goodModel = new \CatalogPlus\Structure\Good\Site\Model('');
        $this->goodModel->setCategoryModel($this->model);
        $this->view->goods = $this->goodModel->getList($page);
        $this->view->pager = $this->goodModel->getPager('page');

        // TODO реализация вывода списка групп и подгруппы текущей активной группы
        $this->view->listCat = $this->model->getListCategory();

    }
}
