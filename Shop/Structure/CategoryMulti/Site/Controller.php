<?php
namespace Shop\Structure\Category\Site;

use Ideal\Core\Pagination;
use Ideal\Core\Request;
use Ideal\Core\Db;

class Controller extends \Ideal\Structure\Part\Site\ControllerAbstract
{
    /* @var $model Model */
    protected $model;

    public function indexAction()
    {
        parent::indexAction();
        $db = Db::getInstance();
        $id = end($this->path);
        $id = $id['ID'];
        $_sql = "SELECT COUNT(*) AS r FROM i_shop_structure_good WHERE idCategory={$id}";
        $countList = $db->queryArray($_sql);
        $countList = $countList[0]['r'] or 1;
        $this->view->goods = $this->view->parts;
        $request = new Request();
        $page = intval($request->page);
        $onPage = 20;

        $pagination = new Pagination();
        $this->view->pages = $pagination->getPages($countList,
            $onPage, $page, $request->getQueryWithout('page'), 'page');
        $this->view->pagePrev = $pagination->getPrev();
        $this->view->pageNext = $pagination->getNext();
        unset($this->view->parts);
        if($this->model->object['lvl'] == 1){
            $this->view->showMainSubMenu = 1;
        }
    }
}