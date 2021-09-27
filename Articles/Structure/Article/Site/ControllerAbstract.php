<?php
namespace Articles\Structure\Article\Site;

use Ideal\Core\Request;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    /**
     * @var $model Model
     */
    public $model;

    public function indexAction()
    {
        parent::indexAction();

        $request = new Request();
        $page = intval($request->page);
        $this->view->parts = $this->model->getList($page);

        $this->view->pager = $this->model->getPager('page');
    }

    public function detailAction()
    {
        $this->templateInit('Articles/Structure/Article/Site/detail.twig');

        // Выдёргиваем заголовок из addonName[key]['content']
        $this->view->header = $this->model->getHeader();

        $this->view->article = $this->model->getPageData();
    }
}
