<?php

namespace Cabinet\Structure\Part\Site;

use Cabinet\Structure\User;

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
        // Если пользователь залогинен, то отдаём главную страницу личного кабинета с общей информацией о пользователе
        if ($loggedUser) {
            $this->view->user = $loggedUser;
            $this->view->link = $this->model->getFullUrl();
            $this->view->lkForm = $this->model->getLkForm($loggedUser);
        } else {
            $this->view->loginForm = $this->model->getLoginForm();
        }
    }

    public function logoutAction()
    {
        User\Model::logout();

        // Перенаправляем пользователя на ту страницу на которой был инициирован процесс выхода
        $url = explode('?', $_SERVER['HTTP_REFERER']);
        header('Location: ' . $url[0]);
    }
}
