<?php
namespace Cabinet\Structure\Part\Site;

use Ideal\Core\Config;
use Ideal\Core\Request;
use Cabinet\Structure\User;
use Cabinet\Structure\Part\Site\AccountForms\AccountForms;

class AjaxControllerAbstract extends \Ideal\Core\AjaxController
{
    /** @var array Основные параметры при ответе для json */
    protected $answer;

    /** @var bool Печатать ли ответ при завершении работы класса */
    protected $notPrint = false;

    /**
     * TODO
     * Нужна ли после регистрации активация через e-mail
     * @var bool
     */
    protected $needActive = true;

    public function __construct()
    {
        if (function_exists('session_status')) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
        } else {
            if (session_id() == '') {
                session_start();
            }
        }
        $this->answer = array(
            'error' => false, // Состояние ошибки
            'text' => '', // Текст о выполнении задачи
            'refresh' => false // Требуется ли обновление страницы после получения данных
        );
    }

    /**
     * Завершение работы ajax запроса и вывод результатов
     */
    public function __destruct()
    {
        if (!$this->notPrint) {
            $this->answer['text'] = trim($this->answer['text']);
            print json_encode($this->answer);
            exit();
        }
    }

    /**
     * Авторизация
     *
     * @throws \Exception
     */
    public function loginAction()
    {
        $this->notPrint = true;
        $config = Config::getInstance();
        $prevStructure = $config->getStructureByName('Cabinet_Part');
        $prevStructure = '0-' . $prevStructure['ID'];
        $this->model = new Model($prevStructure);
        $accountForms = new AccountForms();
        $accountForms->setLink($this->model->getFullUrl());
        $form = $accountForms->getLoginFormObject();
        $request = new Request();
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                $email = strtolower($form->getValue('login'));
                $pass = htmlspecialchars($form->getValue('pass'));
                $userModel = new User\Model('');
                $response = $userModel->userAuthorization($email, $pass);
                echo $response;
                die();
            } else {
                echo 'Вы указали не все данные';
                die();
            }
        } else {
            $response = '';
            switch ($request->subMode) {
                // Генерируем js
                case 'js':
                    $script = <<<JS
                    $('#loginForm').on('form.successSend', function () {
                        location.reload();
                    });
JS;
                    $form->setJs($script);
                    $request->mode = 'js';
                    $form->render();
                    die();
                    break;
                // Генерируем css
                case 'css':
                    $request->mode = 'css';
                    $form->render();
                    die();
                    break;
            }
            return $response;
        }
    }

    /**
     * Сохранение данных о пользователе
     * @throws \Exception
     */
    public function saveAction()
    {
        $this->notPrint = true;
        $request = new Request();
        $config = Config::getInstance();
        $prevStructure = $config->getStructureByName('Cabinet_Part');
        $prevStructure = '0-' . $prevStructure['ID'];
        $this->model = new Model($prevStructure);
        $accountForms = new AccountForms();
        $accountForms->setLink($this->model->getFullUrl());
        $form = $accountForms->getLkFormObject();
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                $userData = array();
                $userData['fio'] = $form->getValue('fname');
                $userData['phone'] = $form->getValue('phone');
                $userData['address'] = $form->getValue('addr');
                $userData['password'] = $form->getValue('pass');

                $userModel = new User\Model('');
                $response = $userModel->saveUserData($userData);
                echo $response;
                die();
            } else {
                echo "Заполнены не все поля.";
                die();
            }
        } else {
            $response = '';
            switch ($request->subMode) {
                // Генерируем js
                case 'js':
                    $script = <<<JS
                    $('#lkForm').on('form.successSend', function () {
                        location.reload();
                    });
JS;
                    $form->setJs($script);
                    $request->mode = 'js';
                    $form->render();
                    die();
                    break;
                // Генерируем css
                case 'css':
                    $request->mode = 'css';
                    $form->render();
                    die();
                    break;
            }
            return $response;
        }
    }

    /**
     * Восстановление пароля
     * @throws \Exception
     */
    public function restoreAction()
    {
        $this->notPrint = true;
        $request = new Request();
        $accountForms = new AccountForms();
        $form = $accountForms->getRestoreFormObject();
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                $email = strtolower($form->getValue('login'));
                $userModel = new User\Model('');
                $response = $userModel->restorePassword($email);
                if (!$response['success']) {
                    echo $response['text'];
                } else {
                    $config = Config::getInstance();
                    $title = 'Восстановление пароля на ' . $config->domain;
                    $this->templateInit('Cabinet/Structure/Part/Site/letter.twig');
                    $this->view->phone = $config->phone;
                    $this->view->email = $config->mailForm;
                    $this->view->domain = $config->domain;
                    $this->view->title = $title;
                    $this->view->clearPass = $response['pass'];
                    $this->view->restore = true;
                    $html = $this->view->render();
                    if ($form->sendMail($config->robotEmail, $email, $title, $html, true)) {
                        echo ' Вам выслан новый пароль.';
                    } else {
                        echo ' Услуга временно недоступна попробуйте позже.';
                    }
                }
                die();
            } else {
                echo 'Указан не верный e-mail';
                die();
            }
        } else {
            $response = '';
            switch ($request->subMode) {
                // Генерируем js
                case 'js':
                    $request->mode = 'js';
                    $form->render();
                    die();
                    break;
                // Генерируем css
                case 'css':
                    $request->mode = 'css';
                    $form->render();
                    die();
                    break;
            }
            return $response;
        }
    }

    /**
     * Регистрация пользователя
     */
    public function registrationAction()
    {
        $this->notPrint = true;
        $request = new Request();
        $accountForms = new AccountForms();
        $form = $accountForms->getRegistrationFormObject();
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                $newUserData = array();
                $newUserData['fio'] = $form->getValue('lastname') . ' ' . $form->getValue('name');
                $newUserData['phone'] = $form->getValue('phone');
                $newUserData['address'] = $form->getValue('addr');
                $newUserData['email'] = $form->getValue('email');

                $userModel = new User\Model('');
                $response = $userModel->userRegistration($newUserData);
                if ($response['success']) {
                    $config = Config::getInstance();
                    $title = 'Регистрация на ' . $config->domain;

                    $this->templateInit('Cabinet/Structure/Part/Site/letter.twig');
                    $this->view->phone = $config->phone;
                    $this->view->email = $config->mailForm;
                    $this->view->domain = $config->domain;
                    $this->view->reg = true;
                    $this->view->fio = $newUserData['fio'];
                    $this->view->email = $newUserData['email'];
                    $this->view->pass = $response['pass'];
                    $link = 'http://' . $config->domain . $form->getValue('link') . '?';
                    $link .= 'action=finishReg';
                    $link .= '&email=' . urlencode($newUserData['email']);
                    $link .= '&key=' . urlencode($response['key']);
                    $this->view->link = $link;
                    $this->view->title = $title;
                    $msg = $this->view->render();

                    if ($form->sendMail($config->robotEmail, $newUserData['email'], $title, $msg, true)) {
                        echo 'Вам было отправлено письмо с инструкцией для дальнейшей регистрации';
                    } else {
                        echo 'Ошибка. Попробуйте чуть позже';
                    }
                } else {
                    echo $response['text'];
                }
                die();
            } else {
                echo "Заполнены не все поля.";
                die();
            }
        } else {
            $response = '';
            switch ($request->subMode) {
                // Генерируем js
                case 'js':
                    $request->mode = 'js';
                    $form->render();
                    die();
                    break;
                // Генерируем css
                case 'css':
                    $request->mode = 'css';
                    $form->render();
                    die();
                    break;
            }
            return $response;
        }
    }
}
