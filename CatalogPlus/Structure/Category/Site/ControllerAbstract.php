<?php
namespace CatalogPlus\Structure\Category\Site;

use Ideal\Core\Request;
use CatalogPlus\Structure\Category\Filter;

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
        $page = intval(substr($request->page, 0, 10));
        if ($page == 0) {
            $page = 1;
        }

        $filter = new Filter();
        $filter->setParams($_GET);

        $this->goodModel = new \CatalogPlus\Structure\Good\Site\Model('');
        $this->goodModel->setCategoryModel($this->model);

        $this->goodModel->setFilter($filter);

        $this->view->goods = $this->goodModel->getList($page);
        if ($this->goodModel->is404) {
            $this->model->is404 = true;
        }
        $this->view->pager = $this->goodModel->getPager('page');

        // TODO реализация вывода списка групп и подгруппы текущей активной группы
        $this->view->listCat = $this->model->getListCategory();

    }
}
