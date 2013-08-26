<?php
namespace Shop\Structure\Good\Site;

class Controller extends \Ideal\Core\Site\Controller
{
    /**
     * @var $model Model
     */
    protected $model;

    public function indexAction()
    {
        parent::indexAction();

        $this->view->good = $this->model->object;
    }

}
