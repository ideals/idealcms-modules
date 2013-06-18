<?php
namespace Articles\Structure\Article\Site;

use Ideal\Core\Request;
use Ideal\Core\Pagination;

class Controller extends \Ideal\Core\Site\Controller
{
    /**
     * @var $model Model
     */
    public $model;

    public function indexAction()
    {
        $this->templateInit();

        $this->model->detectCurrentCategory($this->path);

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

        $this->view->parts = $this->model->getCategories();

        $request = new Request();
        $page = intval($request->page);
        $onPage = $this->model->params['elements_site'];

        if ($page > 1) {
            // На страницах листалки описание категории отображать не надо
            $template = $this->view->template;
            $template['content'] = '';
            $this->view->template = $template;
            // Страницы листалки неиндексируются, но ссылки с них — индексируются
            $this->model->metaTags['robots'] = 'follow, noindex';
        }

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
