<?php
namespace Catalog\Structure\Good\Site;

class Controller extends \Ideal\Core\Site\Controller
{
    /**
     * @var $model Model
     */
    protected $model;

    public function indexAction()
    {
        parent::indexAction();

        $pageData = $this->model->getPageData();
        $this->view->good = $pageData;
    }

}
