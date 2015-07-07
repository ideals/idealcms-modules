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
            $pageData = $this->model->getPageData();
            $pageData['template'] = 'Shop/Structure/Basket/Site/empty.twig';
            $this->model->setPageData($pageData);
            parent::indexAction();
            return;
        }

        $this->view->goods = $basket;
    }

    public function detailAction()
    {
        $this->view->tabs = $this->model->getTabs();
        $t = 1;

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
