<?php
namespace Shop\Structure\Category\Site;

use Ideal\Core\Request;
use Ideal\Core\Db;

class ControllerAbstract extends \Ideal\Structure\Part\Site\ControllerAbstract
{
    public function indexAction()
    {
        parent::indexAction();

        $request = new Request();
        $page = intval($request->page);

        $goods = new \Shop\Structure\Good\Site\Model($this->goodsStructurePath);
        $this->view->goods = $goods->getListByCategory($page, $this->model->object['ID']);

        $this->view->pager = $goods->getPager($page, $request->getQueryWithout('page'));
    }

}
