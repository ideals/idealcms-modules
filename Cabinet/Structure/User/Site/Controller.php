<?php

namespace Cabinet\Structure\User\Site;

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

        foreach ($templatesVars as $k => $v) {
            $this->view->$k = $v;
        }


        if ($_GET['url'] == 'cabinet' && isset($_GET['email']) && isset($_GET['key']))
            $this->view->answer = $this->model->reg($_GET['email'], $_GET['key']);

        // Скрытый раздел
        session_start();
        if ($_SESSION['login']['input']) {
            $this->view->loginUser = $_SESSION['login']['user'];
        }

    }

}