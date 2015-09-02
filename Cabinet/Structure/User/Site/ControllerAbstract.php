<?php

namespace Cabinet\Structure\User\Site;

use Ideal\Core\Config;

class ControllerAbstract extends \Ideal\Structure\Part\Site\Controller
{
    /** @var  Model model */
    protected $model;

    public function indexAction()
    {
        parent::indexAction();Field
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
        $ajaxController = new AjaxController();
        $this->view->recForm = $ajaxController->recoverAction();
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
        $config = Config::getInstance();
        // Проверяем подключена ли структуры корзины
        if ($config->getStructureByName('Shop_Basket') !== false) {
            // Если корзина не пуста, то вызываем метод её сохранения для выходящего пользователя
            $this->model->saveBasket();

            // Удаляем информацию о корзине из куки
            header("Set-Cookie: basket=deleted; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT");
        }
        if (function_exists('session_status')) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
        } else {
            if (session_id() == '') {
                session_start();
            }
        }
        unset($_SESSION['login']);
        $url = explode('?', $_SERVER['HTTP_REFERER']);
        header('Location: ' . $url[0]);
        exit;
    }
}
