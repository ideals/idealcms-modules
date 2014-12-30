<?php
namespace Catalog\Structure\Category\Site;

use Catalog\Structure\Good;
use Ideal\Core\Config;
use Ideal\Core\Request;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    /** @var bool Отображать товары из вложенных категорий */
    protected $isShowSubGoods = false;

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

        // todo отображение товаров для категорий с вложенными категориями
        if ($this->isShowSubGoods) {
            // Определяем модель товаров этой категории
            $prevStructure = $structure['ID'] . '-' . $page['ID'];
            $goods = new Good\Site\Model($prevStructure);
            $goods->setPath($this->model->getPath());
            $goods->setCategoryModel($this->model);
            $this->view->goods = $goods->getList($page);
            $this->view->pager = $goods->getPager('page');
        }

        $request = new Request();
        $page = intval($request->page);
        $this->view->categories = $this->model->getList($page);
    }
}
