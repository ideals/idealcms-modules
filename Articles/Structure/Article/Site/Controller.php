<?php
namespace Articles\Structure\Article\Site;

use Ideal\Core\Request;
use Ideal\Core\Pagination;

class Controller extends \Ideal\Structure\Part\Site\ControllerAbstract
{
    /**
     * @var $model Model
     */
    public $model;

    public function indexAction()
    {
        $this->templateInit();

        $header = '';
        $templatesVars = $this->model->getTemplatesVars();

        if (isset($templatesVars['template']['content'])) {
            list($header, $text) = $this->model->extractHeader($templatesVars['template']['content']);
            $templatesVars['template']['content'] = $text;
        }

        foreach($templatesVars as $k => $v) {
            $this->view->$k = $v;
        }

        $this->view->header = $this->model->getHeader($header);

        $this->view->url = $this->model->object['url'];

        $modelCategory = new \Articles\Structure\Category\Site\Model($this->model->getStructurePath());
        $end = end($this->path);
        $parentUrl = $this->model->getParentUrl();
        $this->view->parts = $modelCategory->getCategories($parentUrl);

        $request = new Request();
        $page = intval($request->page);
        $onPage = $this->model->params['elements_site'];

        $this->view->articles = $this->model->getArticles($page, $onPage);

        // Отображение листалки
        $pagination = new Pagination();
        $this->view->pages = $pagination->getPages($this->model->getArticlesCount(),
            $onPage, $page, $request->getQueryWithout('page'), 'page');
    }


    public function detailAction()
    {
        $this->templateInit('Articles\Structure\Article\Site\detail.twig');

        $this->view->article = $this->model->object;
    }

}
