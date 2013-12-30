<?php
namespace CatalogPlus\Structure\Good\Site;

use Ideal\Core\Request;
use Ideal\Core\Pagination;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    /**
     * @var $model Model
     */
    public $model;
    protected $categoryPrevStructure;

    public function indexAction()
    {
        $this->model->detectCurrentCategory();

        parent::indexAction();

        $request = new Request();
        $page = intval($request->page);
        $this->view->parts = $this->model->getList($page);

        $this->view->pager = $this->model->getPager('page');

        $this->view->categories = $this->model->getCategories();;
    }


    public function detailAction()
    {
        $this->templateInit('Articles/Structure/Article/Site/detail.twig');

        // Выдёргиваем заголовок из template['content']
        $this->view->header = $this->model->getHeader();

        $this->view->article = $this->model->getPageData();
    }

}
