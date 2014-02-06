<?php

namespace Cabinet\Structure\User\Site;

class ControllerAbstract extends \Ideal\Structure\Part\Site\Controller
{
    /** @var  Model model */
    protected $model;

    public function indexAction()
    {
        parent::indexAction();
        session_start();
        if (isset($_SESSION['login']['is_active']) && $_SESSION['login']['is_active']) {
            $this->view->user = $this->model->getUser();
            $this->view->step = 'lk';
        } else {
            $this->view->step = 'login';
        }

    }

    public function regAction()
    {
        parent::indexAction();
        $this->view->step = 'reg';
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
        session_start();
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
        session_start();
        unset($_SESSION['login']);
        $url = explode('?', $_SERVER['HTTP_REFERER']);
        header('Location: ' . $url[0]);
        exit;
    }

}
