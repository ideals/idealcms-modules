<?php
namespace Cabinet\Structure\Part\Site;

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

        session_start();
        if ($_SESSION['login']['input']) {
            $this->view->loginUser = $_SESSION['login']['user'];
            if($_SESSION['login']['input'] === 2){
                $this->view->header = 'Вашу учетную запись еще не активировали';
                return;
            }

            $templatesVars = $this->model->getTemplatesVars();

            if (isset($templatesVars['template']['content'])) {
                list($header, $text) = $this->model->extractHeader($templatesVars['template']['content']);
                $templatesVars['template']['content'] = $text;
            }

            foreach ($templatesVars as $k => $v) {
                $this->view->$k = $v;
            }

            $this->view->header = $this->model->getHeader($header);

            $this->view->parts = $this->model->getList(1, 999);
        }

    }
}