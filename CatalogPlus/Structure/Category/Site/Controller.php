<?php
namespace CatalogPlus\Structure\Category\Site;

use \CatalogPlus\Structure\Category\Widget\CategoriesList;

class Controller extends ControllerAbstract
{
    /** @var structure_path для товаров, связанных с этими категориями */
    protected $goodsPrevStructure = '0-6';

    public function indexAction(){
        parent::indexAction();
        $this->view->parts = $this->model->getList(999);

        $good = $this->model->getAllGoods();
        $this->view->goods = $good;

        $listCat = new CategoriesList($this->model);
        $listCat->setPrefix('/shop');
        $this->view->listCat = $listCat->getData();
    }

}