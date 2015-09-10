<?php
namespace Shop\Structure\Basket\Site;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    /** @var  Model model */
    protected $model;

    public function indexAction()
    {
        $basket = $this->model->getGoods();

        if (empty($basket)) {
            // Если в корзине нет товаров — выводим шаблон с пустой корзиной
            $this->model->setEmptyBasket();
            parent::indexAction();
            return;
        } else {
            parent::indexAction();
            $this->view->firstTab = $this->model->getFirstTab();
        }

        $this->view->goods = $basket;
    }

    public function detailAction()
    {
        $basket = $this->model->getGoods();
        $tabsInfo = $this->model->getTabsInfo();

        if (empty($basket)) {
            // Если в корзине нет товаров — выводим шаблон с пустой корзиной
            $this->model->setEmptyBasket();
            parent::indexAction();
            return;
        }

        parent::indexAction();
        $this->view->goods = $basket;
        $this->view->tabsInfo = $tabsInfo;

        $tabs = $this->model->getTabs();
        $this->view->tabs = $tabs;
        $this->view->currentTabId = $this->model->getCurrentTabId($tabs);

        /*if (isset($_GET['tab'])) {
            $this->view->tab = (int)$_GET['tab'];
            $this->view->{'tab' . (int)$_GET['tab']} = 'current';
        } else {
            $this->view->tab = 0;
            $this->view->tab0 = 'current';
        }*/

        /*// TODO получение информации о пользователе(покупателе)
        $user = \Shop\Structure\Order\Site\Model::getContactUser();
        foreach ($user as $k => $v) {
            $this->view->{$k} = $v;
        }*/
    }
}
