<?php

namespace CatalogPlus\Structure\Category\Site;

use Ideal\Core\Site\Controller;
use CatalogPlus\Structure\Good\Site\Model;
use Ideal\Core\Request;
use CatalogPlus\Structure\Category\Filter;

class ControllerAbstract extends Controller
{
    /* @var $model Model Модель соответствующая этому контроллеру */
    protected $model;

    /** @var $goodModel \CatalogPlus\Structure\Good\Site\Model Модель товаров */
    protected $goodModel;

    /** @var string $pageParamName Название параметра определяющего номер страницы*/
    protected $pageParamName = 'page';

    public function indexAction(): void
    {
        parent::indexAction();

        $request = new Request();
        $page = (int) substr($request->{$this->pageParamName}, 0, 10);
        if ($page === 0) {
            $page = null;
        } else {
            $page = $page === 0 ? $page = 1 : $page;
        }

        $this->model->setPageNum($page);

        $filter = new Filter();
        $filter->setParams($_REQUEST);

        $this->goodModel = new Model('');
        $this->goodModel->setCategoryModel($this->model);

        $this->goodModel->setFilter($filter);

        $this->view->goods = $this->goodModel->getList($page);
        if ($this->goodModel->is404) {
            $this->model->is404 = true;
        }

        $this->view->pager = $this->goodModel->getPager($this->pageParamName);

        // TODO реализация вывода списка групп и подгруппы текущей активной группы
        $this->view->listCat = $this->model->getListCategory();
    }
}
