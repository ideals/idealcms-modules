<?php
namespace Shop\Structure\Basket\Site;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    /** @var  Model model */
    protected $model;

    public function indexAction()
    {
        $basket = $this->model->calcFullBasket();

        if (empty($basket) || (isset($basket['total']) && empty($basket['total']))) {
            // Если в корзине нет товаров — выводим шаблон с пустой корзиной
            $this->emptyAction();
            return;
        }

        parent::indexAction();
        $this->view->firstTab = $this->model->getFirstTab();

        $this->view->goods = $basket;
    }

    public function detailAction()
    {
        $basket = $this->model->getBasket();
        $tabsInfo = $this->model->getTabsInfo();

        if (empty($basket) || (isset($basket['total']) && empty($basket['total']))) {
            // Если в корзине нет товаров — выводим шаблон с пустой корзиной
            $this->emptyAction();
            return;
        }

        parent::indexAction();
        $this->view->goods = $basket;
        $this->view->tabsInfo = $tabsInfo;

        $tabs = $this->model->getTabs();
        $this->view->tabs = $tabs;
        $this->view->currentTabId = $this->model->getCurrentTabId($tabs);
    }

    /**
     * Отображение пустой корзины
     */
    public function emptyAction()
    {
        $this->templateInit('Shop/Structure/Basket/Site/empty.twig');
    }
}
