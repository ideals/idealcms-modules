<?php
namespace CatalogPlus\Structure\Good\Site;

use Ideal\Core\Config;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    public function indexAction()
    {
        parent::indexAction();
        $pageData = $this->model->getPageData();
        if (isset($pageData['structure']) && ($pageData['structure'] === 'CatalogPlus_Good')) {
            // Главная страница товаров не должна вызываться. Списки товаров только в категориях
            $this->error404Action();
            return;
        }
        $config = Config::getInstance();
        $offers = $config->getStructureByName('CatalogPlus_Offer');
        if ($offers) {
            $this->view->offers = $this->model->getGoodsInfo(array($pageData['ID']));
        }
    }
}
