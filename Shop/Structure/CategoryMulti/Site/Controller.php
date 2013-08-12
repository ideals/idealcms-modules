<?php
namespace Shop\Structure\CategoryMulti\Site;

use Ideal\Core\Pagination;
use Ideal\Core\Request;
use Ideal\Core\Db;

class Controller extends \Ideal\Structure\Part\Site\ControllerAbstract
{
    /* @var $model Model */
    protected $model;
    protected $isPager = true;

    public function indexAction()
    {
        $this->model->params['elements_site'] = 20;
        parent::indexAction();
        if($this->model->object['lvl'] == 1){
            $this->view->showMainSubMenu = 1;
        }
    }
}