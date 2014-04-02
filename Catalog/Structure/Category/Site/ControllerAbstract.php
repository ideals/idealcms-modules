<?php
namespace Catalog\Structure\Category\Site;

use Catalog\Structure\Good;
use Ideal\Core\Config;
use Ideal\Core\Request;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    /**
     * В этот экшен мы попадаем, когда в категории есть вложенные категории,
     * а не сразу же идёт список товара
     */
    public function indexAction()
    {
        parent::indexAction();

        $page = $this->model->getPageData();
        $config = Config::getInstance();
        $structure = $config->getStructureByClass(get_class($this));

        // Определяем модель товаров этой категории
        $prevStructure = $structure['ID'] . '-' . $page['ID'];
        $goods = new Good\Site\Model($prevStructure);
        $goods->setPath($this->model->getPath());
        $goods->setCategoryModel($this->model);

        $request = new Request();
        $page = intval($request->page);
        $this->view->goods = $goods->getList($page);
        $this->view->pager = $goods->getPager('page');
    }
}
