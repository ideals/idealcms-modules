<?php

namespace Gallery\Structure\Fancy\Site;

use Ideal\Core\Site\Controller;
use Ideal\Core\Request;

class ControllerAbstract extends Controller
{
    /**
     * @var $model Model
     */
    public $model;

    public function indexAction(): void
    {
        parent::indexAction();

        $request = new Request();
        $page = intval($request->page);
        $this->view->photos = $this->model->getList($page);

        $this->view->pager = $this->model->getPager($page, $request->getQueryWithout('page'));
    }
}
