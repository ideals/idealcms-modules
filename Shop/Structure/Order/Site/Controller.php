<?php
namespace Shop\Structure\Order\Site;

class Controller extends \Ideal\Structure\Part\Site\ControllerAbstract
{
    public function indexAction()
    {
        $this->templateInit();

        $templatesVars = $this->model->getTemplatesVars();

        if (isset($templatesVars['template']['content'])) {
            list($header, $text) = $this->model->extractHeader($templatesVars['template']['content']);
            $templatesVars['template']['content'] = $text;
        }

        foreach($templatesVars as $k => $v) {
            $this->view->$k = $v;
        }

        $this->view->goods = $this->model->getGoods();
    }
}