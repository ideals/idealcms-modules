<?php
namespace CatalogPlus\Structure\Category\Site;

use \CatalogPlus\Structure\Category\Widget\CategoriesList;
use \Shop\Structure\Type\Widget\TypeList;

class Controller extends ControllerAbstract
{
    /** @var structure_path для товаров, связанных с этими категориями */
    protected $goodsPrevStructure = '0-6';

    public function indexAction()
    {
        parent::indexAction();
        $this->view->parts = $this->model->getList(999);

        $goods = $this->model->getAllGoods();
        $this->view->goods = $goods;

        $url = explode('/',$_GET['url']);
        $brandActive= false;
        if($url[1] == 'brand') $brandActive = true;

        $brand = new TypeList('');
        $brand->setPrefix('/catalog/brand');
        $brand->setPrevStructure('3-2');
        $brand = $brand->getData($url[2]);
        $brand = array('name'=>'Brand','isActivePage' => $brandActive, 'subCategoryList'=>$brand);

        $listCat = new CategoriesList($this->model);
        $listCat->setPrefix('/catalog');
        $listCat = $listCat->getData();
        array_unshift($listCat,$brand);
        $this->view->listCat = $listCat;

    }

}