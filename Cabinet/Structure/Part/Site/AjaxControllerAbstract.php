<?php

namespace Cabinet\Structure\Part\Site;

use Ideal\Core\AjaxController;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Cabinet\Structure\User;
use Cabinet\Structure\Part\Site\AccountForms\AccountForms;

class AjaxControllerAbstract extends AjaxController
{
    /** @var array Дополнительные HTTP-заголовки ответа  */
    public $httpHeaders = [];

    /**
     * TODO
     * Нужна ли после регистрации активация через e-mail
     * @var bool
     */
    protected $needActive = true;

    public function __construct()
    {
        if (function_exists('session_status')) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
        } elseif (session_id() == '') {
            session_start();
        }
    }

    /**
     * Авторизация
     *
     * @throws \Exception
     */
    public function loginAction()
    {
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
                return $userModel->userAuthorization($email, $pass);
            }

            return 'Вы указали не все данные';

        }

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
                $form->setSendHeader(false);
                $this->httpHeaders['Content-type'] = 'application/javascript';
                ob_start();
                $form->render();
                $text = ob_get_contents();
                ob_end_clean();
                return $text;
                // Генерируем css
            case 'css':
                $request->mode = 'css';
                $form->setSendHeader(false);
                $this->httpHeaders['Content-type'] = 'text/css';
                ob_start();
                $form->render();
                $text = ob_get_contents();
                ob_end_clean();
                return $text;
        }

        return $response;

    }

    /**
     * Сохранение данных о пользователе
     * @throws \Exception
     */
    public function saveAction()
    {
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
                $userData = [];
                $userData['fio'] = $form->getValue('fname');
                $userData['phone'] = $form->getValue('phone');
                $userData['address'] = $form->getValue('addr');
                $userData['password'] = $form->getValue('pass');

                $userModel = new User\Model('');
                return $userModel->saveUserData($userData);
            }

            return "Заполнены не все поля.";

        }

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
                $form->setSendHeader(false);
                $this->httpHeaders['Content-type'] = 'application/javascript';
                ob_start();
                $form->render();
                $text = ob_get_contents();
                ob_end_clean();
                return $text;
                // Генерируем css
            case 'css':
                $request->mode = 'css';
                $form->setSendHeader(false);
                $this->httpHeaders['Content-type'] = 'text/css';
                ob_start();
                $form->render();
                $text = ob_get_contents();
                ob_end_clean();
                return $text;
        }

        return $response;

    }

    /**
     * Восстановление пароля
     * @throws \Exception
     */
    public function restoreAction()
    {
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
                        return ' Вам выслан новый пароль.';
                    }

                    return ' Услуга временно недоступна попробуйте позже.';

                }
            } else {
                return 'Указан не верный e-mail';
            }
        } else {
            $response = '';
            switch ($request->subMode) {
                // Генерируем js
                case 'js':
                    $request->mode = 'js';
                    $form->setSendHeader(false);
                    $this->httpHeaders['Content-type'] = 'application/javascript';
                    ob_start();
                    $form->render();
                    $text = ob_get_contents();
                    ob_end_clean();
                    return $text;
                    // Генерируем css
                case 'css':
                    $request->mode = 'css';
                    $form->setSendHeader(false);
                    $this->httpHeaders['Content-type'] = 'text/css';
                    ob_start();
                    $form->render();
                    $text = ob_get_contents();
                    ob_end_clean();
                    return $text;
            }

            return $response;
        }

        return null;
    }

    /**
     * Регистрация пользователя
     */
    public function registrationAction()
    {
        $request = new Request();
        $accountForms = new AccountForms();
        $form = $accountForms->getRegistrationFormObject();
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                $newUserData = [];
                $newUserData['fio'] = $form->getValue('lastname') . ' ' . $form->getValue('name');
                $newUserData['phone'] = $form->getValue('phone');
                $newUserData['address'] = $form->getValue('addr');
                $newUserData['email'] = $form->getValue('email');

                $userModel = new User\Model('');
                $response = $userModel->userRegistration($newUserData);
                if ($response['success']) {
                    $config = Config::getInstance();
                    $siteName = empty($config->siteName) ? $config->domain : $config->siteName;
                    $title = 'Регистрация на сайте ' . $siteName;
                    $topic = 'Регистрация на сайте ' . $config->domain;

                    $this->templateInit('Cabinet/Structure/Part/Site/letter.twig');
                    $this->view->phone = $config->phone;
                    $this->view->email = $config->mailForm;
                    $this->view->domain = $config->domain;
                    $this->view->reg = true;
                    $this->view->fio = $newUserData['fio'];
                    $this->view->email = $newUserData['email'];
                    $this->view->pass = $response['pass'];
                    $link = 'https://' . $config->domain . $form->getValue('link') . '?';
                    $link .= 'action=finishReg';
                    $link .= '&email=' . urlencode($newUserData['email']);
                    $link .= '&key=' . urlencode($response['key']);
                    $this->view->link = $link;
                    $this->view->title = $title;
                    $msg = $this->view->render();

                    $smtp = $config->smtp;

                    if ($form->sendMail($config->robotEmail, $newUserData['email'], $topic, $msg, true, $smtp)) {
                        return 'Вам было отправлено письмо с инструкцией для дальнейшей регистрации';
                    }

                    return 'Ошибка. Попробуйте чуть позже';

                }

                return $response['text'];

            }

            return "Заполнены не все поля.";

        }

        $response = '';
        switch ($request->subMode) {
            // Генерируем js
            case 'js':
                $request->mode = 'js';
                $form->setSendHeader(false);
                $this->httpHeaders['Content-type'] = 'application/javascript';
                ob_start();
                $form->render();
                $text = ob_get_contents();
                ob_end_clean();
                return $text;
                // Генерируем css
            case 'css':
                $request->mode = 'css';
                $form->setSendHeader(false);
                $this->httpHeaders['Content-type'] = 'text/css';
                ob_start();
                $form->render();
                $text = ob_get_contents();
                ob_end_clean();
                return $text;
        }

        return $response;

    }

    /**
     * Переопределяет HTTP-заголовки ответа
     *
     * @return array Массив где ключи - названия заголовков, а значения - содержание заголовков
     */
    public function getHttpHeaders()
    {
        return $this->httpHeaders;
    }
}
