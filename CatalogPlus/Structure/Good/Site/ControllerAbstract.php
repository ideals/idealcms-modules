<?php
namespace CatalogPlus\Structure\Good\Site;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    public function indexAction(){
        parent::indexAction();
    }

    /**
     * Отображение карточки товара (подробного описания)
     */
    public function detailAction()
    {
        $this->setTemplate('CatalogPlus/Structure/Good/Site/detail.twig');
        parent::indexAction();
        $this->view->data = $this->model->getData();
    }
}
