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

            // Формируем название метода для получения формы
            $formMethodName = 'get' . ucfirst($formName) . 'Form';
            $this->view->form = $accountForms->$formMethodName();
        }
    }

    /**
     * Метод обрабатывающий реакцию на выход пользователя из личного кабинета
     */
    public function logoutAction()
    {
        User\Model::logout();

        // Перенаправляем пользователя на ту страницу на которой был инициирован процесс выхода
        $url = explode('?', $_SERVER['HTTP_REFERER']);
        header('Location: ' . $url[0]);
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
            default:
                return '';
        }
    }
}
