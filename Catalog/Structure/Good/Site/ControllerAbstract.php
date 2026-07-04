<?php

namespace Catalog\Structure\Good\Site;

use Ideal\Core\Site\Controller;
use Ideal\Core\Request;

class ControllerAbstract extends Controller
{
    /**
     * Отображение списка товаров из одной категории
     */
    public function indexAction(): void
    {
        parent::indexAction();

        $request = new Request();
        $page = intval($request->page);
        $this->view->goods = $this->model->getList($page);
        $this->view->pager = $this->model->getPager('page');
    }

    /**
     * Отображение карточки товара (подробного описания)
     */
    public function detailAction(): void
    {
        $this->setTemplate('Catalog/Structure/Good/Site/detail.twig');
        parent::indexAction();
    }
}
