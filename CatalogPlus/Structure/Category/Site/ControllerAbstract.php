<?php
namespace CatalogPlus\Structure\Category\Site;

use Ideal\Core\Request;
use Ideal\Core\Db;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    /** @var prev_structure для товаров, связанных с этими категориями */
    protected $goodsPrevStructure;

    public function indexAction()
    {
        parent::indexAction();

        $request = new Request();
        $page = intval($request->page);

        $goods = new \CatalogPlus\Structure\Good\Site\Model($this->goodsPrevStructure);
        $path = $this->model->getPath();
        $goods->setCategory(end($path));

        $this->view->goods = $goods->getList($page);
        $this->view->pager = $goods->getPager('page');
    }

}
