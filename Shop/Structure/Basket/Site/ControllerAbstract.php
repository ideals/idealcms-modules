<?php
namespace Shop\Structure\Basket\Site;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    /** @var  Model model */
    protected $model;

    public function indexAction()
    {
        $basket = $this->model->getGoods();
        $hideBasket = false;
        if (!isset($basket['goods']) || !(count($basket['goods']) > 0)) {
            $hideBasket = true;
            $this->templateInit('Shop/Structure/Basket/Site/empty.twig');
        } else {
            $this->templateInit();
        }

        $this->view->hideBasket = $hideBasket;

        // Выдёргиваем заголовок из template['content']
        $this->view->header = $this->model->getHeader();

        // Перенос данных страницы в шаблон
        $pageData = $this->model->getPageData();
        foreach ($pageData as $k => $v) {
            $this->view->$k = $v;
        }
        if ($hideBasket) {
            return;
        }

        $this->view->goods = $basket;

        if (isset($_GET['tab'])) {
            $this->view->tab = (int)$_GET['tab'];
            $this->view->{'tab' . (int)$_GET['tab']} = 'current';
        } else {
            $this->view->tab = 0;
            $this->view->tab0 = 'current';
        }

        /*// TODO получение информации о пользователе(покупателе)
        $user = \Shop\Structure\Order\Site\Model::getContactUser();
        foreach ($user as $k => $v) {
            $this->view->{$k} = $v;
        }*/
    }
}
