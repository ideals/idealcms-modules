<?php
namespace Catalog\Structure\Good\Site;

use Ideal\Core\Request;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    /**
     * Отображение списка товаров из одной категории
     */
    public function indexAction()
    {
        $this->setTemplate('Catalog/Structure/Category/Site/index.twig');
        parent::indexAction();

        $request = new Request();
        $page = intval($request->page);
        $this->view->goods = $this->model->getList($page);
        $this->view->pager = $this->model->getPager('page');
    }

    /**
     * Отображение карточки товара (подробного описания)
     */
    public function detailAction()
    {
        $this->setTemplate('Catalog/Structure/Good/Site/detail.twig');
        parent::indexAction();
    }
}