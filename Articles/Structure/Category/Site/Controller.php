<?php
namespace Articles\Structure\Category\Site;

use Ideal\Core\Request;
use Ideal\Core\Pagination;

class Controller extends \Ideal\Structure\Part\Site\ControllerAbstract
{
    /* @var $model Model */
    protected $model;

    public function indexAction()
    {
        parent::indexAction();

        $this->view->url = $this->model->object['url'];

        $this->view->parts = $this->model->getCategories();

        $request = new Request();
        $page = intval($request->page);
        $onPage = $this->model->params['elements_site'];

        $this->view->goods = $this->model->getArticles($page, $onPage);

        // Отображение листалки
        $pagination = new Pagination();
        $this->view->pages = $pagination->getPages($this->model->getArticlesCount(),
            $onPage, $page, $request->getQueryWithout('page'), 'page');
    }
}
