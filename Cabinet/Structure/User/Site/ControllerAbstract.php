<?php

namespace Cabinet\Structure\User\Site;

class ControllerAbstract extends \Ideal\Structure\Part\Site\Controller
{
    /** @var  Model model */
    protected $model;

    public function indexAction()
    {
        parent::indexAction();
        $link = $this->model->getFullUrl();
        if (session_id() == "") {
            session_start();
        }
        if (isset($_SESSION['login']['is_active']) && $_SESSION['login']['is_active']) {
            $user = $this->model->getUser();
            $this->view->user = $user;
            $this->view->step = 'lk';
            $ajaxController = new AjaxController();
            $this->view->lkForm = $ajaxController->saveAction($user);
        } else {
            $this->view->step = 'login';
            $ajaxController = new AjaxController();
            $this->view->loginForm = $ajaxController->loginAction($link);
        }

        $this->view->link = $link;

    }

    public function regAction()
    {
        parent::indexAction();
        $this->view->step = 'reg';

        $link = $this->model->getFullUrl();
        $ajaxController = new AjaxController();
        $this->view->regForm = $ajaxController->registrationAction($link);

        $this->view->link = $this->model->getFullUrl();
    }

    public function recAction()
    {
        parent::indexAction();
        $this->view->step = 'rec';

    }

    /**
     * Подтверждение регистрации
     */
    public function finishRegAction()
    {
        parent::indexAction();
        if (!isset($_SESSION)) {
            session_start();
        }
        $this->view->step = 'finishReg';
        if (isset($_GET['email']) && isset($_GET['key'])) {
            $this->model->finishReg();
            if (isset($_SESSION['login']['is_active'])) $_SESSION['login']['is_active'] = true;
        }
    }

    /**
     * Выход пользователя
     */
    public function logoutAction()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        unset($_SESSION['login']);
        $url = explode('?', $_SERVER['HTTP_REFERER']);
        header('Location: ' . $url[0]);
        exit;
    }
}
