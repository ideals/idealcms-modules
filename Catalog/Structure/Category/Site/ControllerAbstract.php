<?php
namespace Catalog\Structure\Category\Site;

use Ideal\Core\Request;
use Ideal\Core\Db;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    /** @var string prev_structure для товаров, связанных с этими категориями */
    protected $goodsPrevStructure;

    public function detailAction()
    {
        parent::indexAction();

        $request = new Request();
        $page = intval($request->page);

        $goods = new \Catalog\Structure\Good\Site\Model($this->goodsPrevStructure);
        $goods->setCategory($this->model->getPageData());

        $this->view->goods = $goods->getList($page);
        $this->view->pager = $goods->getPager('page');
    }

}
