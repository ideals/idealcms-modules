<?php
namespace Shop\Structure\Good\Site;

class Controller extends \Ideal\Structure\Part\Site\ControllerAbstract
{
    /**
     * @var $model Model
     */
    protected $model;

    public function indexAction()
    {
        $this->templateInit();

        //$this->model->detectCurrentCategory($this->path);

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

        $this->view->good = $this->model->object;
    }

}
