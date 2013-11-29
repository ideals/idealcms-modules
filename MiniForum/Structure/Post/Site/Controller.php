<?php
namespace MiniForum\Structure\Post\Site;

use Ideal\Core\Request;
use Ideal\Structure\User;

class Controller extends \MiniForum\Structure\Post\Site\ControllerAbstract
{
    protected $model;

    public function indexAction()
    {
        parent::parentIndexAction();
        $this->view->Authorized = $_SESSION['IsAuthorized'];
        $this->view->posts = $this->view->parts;
        $request = new Request();
        $page = intval($request->page);
        if ($page !== 0) {
            $title = 'Форум об аутизме и проблемах обучения - Страница ' . $page;
            $this->model->setTitle($title);
        }

        // Регистрируем объект пользователя
        $user = User\Model::getInstance();
        $_SESSION['IsAuthorized'] = false;
        // Если пользователь залогинен
        if ($user->checkLogin()) {
            $_SESSION['IsAuthorized'] = true;
        }
    }

}