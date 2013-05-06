<?php
namespace Shop\Structure\Good\Site;

class Controller extends \Ideal\Structure\Part\Site\ControllerAbstract
{

    public function indexAction()
    {
        $this->templateInit();

        $this->view->good = $this->model->object;
    }

}
