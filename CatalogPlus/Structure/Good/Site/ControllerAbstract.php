<?php
namespace CatalogPlus\Structure\Good\Site;

use Ideal\Core\Config;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    public function indexAction()
    {
        parent::indexAction();
        $config = Config::getInstance();
        $offers = $config->getStructureByName('CatalogPlus_Offer');
        if ($offers) {
            $pageData = $this->model->getPageData();
            $this->view->offers = $this->model->getGoodsInfo(array($pageData['ID']));
        }
    }
}
