<?php

namespace Cabinet\Structure\Part\Site;

use Cabinet\Structure\User;
use Cabinet\Structure\Part\Site\AccountForms\AccountForms;

class ControllerAbstract extends \Ideal\Structure\Part\Site\Controller
{
    /** @var  Model model */
    protected $model;

    /**
     * Метод обрабатывающий реакцию на открытие главной страницы личного кабинета
     */
    public function indexAction()
    {
        parent::indexAction();

        // Для модели пользователя преструктура не важна
        $user = new User\Model('');
        $loggedUser = $user->getLoggedUser();

        $accountForms = new AccountForms();
        $accountForms->setLink($this->model->getFullUrl());
        // Если пользователь залогинен, то отдаём главную страницу личного кабинета с общей информацией о пользователе
        if ($loggedUser) {
            $this->view->user = $loggedUser;
            $this->view->link = $this->model->getFullUrl();
            $this->view->lkForm = $accountForms->getLkForm($loggedUser);
        } else {
            $this->view->loginForm = $accountForms->getLoginForm();
        }
    }

    /**
     * Метод обрабатывающий реакцию на открытие внутренних страниц личного кабинета
     */
    public function detailAction()
    {
        $pageData = $this->model->getPageData();
        $template = basename($pageData['template']);
        $this->templateInit('Cabinet/Structure/Part/Site/' . $template);

        // Перенос данных страницы в шаблон
        foreach ($pageData as $k => $v) {
            $this->view->$k = $v;
        }

        $this->view->header = $this->model->getHeader();

        // Получаем название формы для текущей страницы
        $formName = $this->getFormName();
        if (!empty($formName)) {
            $accountForms = new AccountForms();
            $accountForms->setLink($this->model->getFullUrl());

            // Формируем название метода для получения формы
            $formMethodName = 'get' . ucfirst($formName) . 'Form';
            $this->view->form = $accountForms->$formMethodName();
        }
    }

    /**
     * Подтверждение регистрации
     */
    public function finishRegAction()
    {
        $this->templateInit('Cabinet/Structure/Part/Site/finishReg.twig');
        if (function_exists('session_status')) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
        } else {
            if (session_id() == '') {
                session_start();
            }
        }
        if (isset($_GET['email']) && isset($_GET['key'])) {
            $user = new User\Model('');
            $user->finishReg();
            if (isset($_SESSION['login']['is_active'])) $_SESSION['login']['is_active'] = true;
        }
    }

    /**
     * Метод обрабатывающий реакцию на выход пользователя из личного кабинета
     */
    public function logoutAction()
    {
        User\Model::logout();

        // Перенаправляем пользователя на ту страницу на которой был инициирован процесс выхода
        if (isset($_SERVER['HTTP_REFERER'])) {
            $url = explode('?', $_SERVER['HTTP_REFERER']);
            $url = $url[0];
        } else {
            $url = '/';
        }
        header('Location: ' . $url);
        die();
    }

    /**
     * Получает наименование формы для просматриваемой внутренней страницы личного кабинета
     */
    public function getFormName()
    {
        $pageData = $this->model->getPageData();
        $template = basename($pageData['template']);
        switch ($template) {
            case 'restorePassword.twig':
                return 'restore';
                break;
            case 'registration.twig':
                return 'registration';
                break;
            default:
                return '';
        }
    }
}
