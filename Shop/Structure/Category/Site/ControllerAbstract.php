<?php
namespace Shop\Structure\Category\Site;

use Ideal\Core\Request;
use Ideal\Core\Db;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    /** @var structure_path для товаров, связанных с этими категориями */
    protected $goodsStructurePath;

    public function indexAction()
    {
        parent::indexAction();

        $request = new Request();
        $page = intval($request->page);

        $goods = new \Shop\Structure\Good\Site\Model($this->goodsStructurePath);
        $goods->setCategory($this->model->object);

        $this->view->goods = $goods->getList($page);
        $this->view->pager = $goods->getPager($page, $request->getQueryWithout('page'));
    }

}
