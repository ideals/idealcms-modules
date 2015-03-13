<?php
namespace CatalogPlus\Structure\Brand\Site;

use Ideal\Structure\Part;
use Ideal\Core\Request;

class ControllerAbstract extends Part\Site\Controller
{
    public function detailAction()
    {
        $this->templateInit('CatalogPlus/Structure/Brand/Site/detail.twig');
        $this->view->pager = $this->model->getPager('page');
        $this->view->goods = $this->model->getGoods();
        $this->view->header = $this->model->getHeader();
        $this->view->metaTags = $this->model->getMetaTags();

        foreach ($this->model->getPageData() as $k => $v) {
            $this->view->{$k} = $v;
        }

        $request = new Request();
        $page = intval($request->page);

        if ($page > 1) {
            // Ќа страницах листалки описание категории отображать не надо
            $this->view->annot = '';
            // —траницы листалки неиндексируютс€, но ссылки с них Ч индексируютс€
            $this->model->metaTags['robots'] = 'follow, noindex';
        }
    }
}
