<?php
namespace Cabinet\Structure\User\Site;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Util;
use Mail\Sender;
use FormPhp;
use Ideal\Core\Request;

class AjaxControllerAbstract extends \Ideal\Core\AjaxController
{
    /** @var array Основные параметры при ответе для json */
    protected $answer;

    protected $data;

    /** @var \Ideal\Core\View */
    protected $view;

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
        $this->loadData();
        if ($this->answer['error']) {
            exit();
        }
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
     * Получение данных о пользователе
     */
    public function getUserAction()
    {
        if (!isset($_SESSION['login']['user'])) {
            $this->answer['error'] = true;
            $this->answer['text'] = "Вы не авторизованы";
            json_encode($this->answer);
            exit;
        }
        if (!isset($_SESSION['login']['data'])) {
            $db = Db::getInstance();
            $config = Config::getInstance();
            $table = $config->db['prefix'] . 'cabinet_structure_user';
            $email = $_SESSION['login']['user'];
            $_sql = "SELECT fio, phone, city, postcode, address FROM {$table} WHERE email='{$email}'";
            $result = $db->select($_sql);
            $_SESSION['login']['data'] = $result[0];
        }
        $this->answer['data'] = $_SESSION['login']['data'];
        $this->answer['data']['login'] = $_SESSION['login']['user'];
        print json_encode($this->answer);
        exit;
    }

    /**
     * Авторизация
     *
     * @param string $link Абсолютный путь до страницы авторизации
     * @throws \Exception
     */
    public function loginAction($link = '')
    {
        $this->notPrint = true;
        $request = new Request();
        $form = new FormPhp\Forms('loginForm');
        $form->setAjaxUrl('/?mode=ajax&controller=\\\\Cabinet\\\\Structure\\\\User\\\\Site&action=login');
        $form->add('login', 'text');
        $form->add('pass', 'text');
        $form->setValidator('login', 'required');
        $form->setValidator('login', 'email');
        $form->setValidator('pass', 'required');
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                $email = strtolower($form->getValue('login'));
                $pass = htmlspecialchars($form->getValue('pass'));
                $config = Config::getInstance();
                $prevStructure = $config->getStructureByName('Cabinet_User');
                $prevStructure = '0-' . $prevStructure['ID'];
                $cabinetUserModel = new Model($prevStructure);
                $response = $cabinetUserModel->userAuthorization($email, $pass);
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
                // Генерируем стартовую часть формы
                case false:
                    $this->templateInit('Cabinet/Structure/User/Site/loginForm.twig');
                    $this->view->start = $form->start();
                    $this->view->link = $link;
                    $formHtml = $this->view->render();
                    $form->setText($formHtml);
                    $response = $form->getText();
                    break;
            }
            return $response;
        }
    }

    /**
     * Регистрация пользователя
     */
    public function registrationAction($link = '')
    {
        $this->notPrint = true;
        $request = new Request();
        $form = new FormPhp\Forms('registrationForm');
        $form->setAjaxUrl('/?mode=ajax&controller=\\\\Cabinet\\\\Structure\\\\User\\\\Site&action=registration');
        $form->setClearForm(false);
        $form->add('lastname', 'text');
        $form->add('name', 'text');
        $form->add('phone', 'text');
        $form->add('addr', 'text');
        $form->add('email', 'text');
        $form->add('int', 'text');
        $form->add('link', 'text');
        $form->setValidator('lastname', 'required');
        $form->setValidator('name', 'required');
        $form->setValidator('phone', 'required');
        $form->setValidator('phone', 'phone');
        $form->setValidator('addr', 'required');
        $form->setValidator('email', 'required');
        $form->setValidator('email', 'email');
        $form->setValidator('int', 'required');
        $form->setValidator('int', 'captcha');
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                $newUserData = array();
                $newUserData['fio'] = $form->getValue('lastname') . ' ' . $form->getValue('name');
                $newUserData['phone'] = $form->getValue('phone');
                $newUserData['address'] = $form->getValue('addr');
                $newUserData['email'] = $form->getValue('email');

                $config = Config::getInstance();
                $prevStructure = $config->getStructureByName('Cabinet_User');
                $prevStructure = '0-' . $prevStructure['ID'];
                $cabinetUserModel = new Model($prevStructure);
                $response = $cabinetUserModel->userRegistration($newUserData);
                if ($response['success']) {
                    $title = 'Регистрация на ' . $config->domain;

                    $this->templateInit('Cabinet/Structure/User/Site/letter.twig');
                    $this->loadHelpVar();

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
                case false:
                    $this->templateInit('Cabinet/Structure/User/Site/registrationForm.twig');
                    $this->view->start = $form->start();
                    $this->view->link = $link;
                    $formHtml = $this->view->render();
                    $form->setText($formHtml);
                    $response = $form->getText();
                break;
            }
            return $response;
        }
    }

    /**
     * Восстановление пароля
     * @throws \Exception
     */
    public function recoverAction()
    {
        $this->notPrint = true;
        $request = new Request();
        $form = new FormPhp\Forms('recoverForm');
        $form->setAjaxUrl('/?mode=ajax&controller=\\\\Cabinet\\\\Structure\\\\User\\\\Site&action=recover');
        $form->add('login', 'text');
        $form->setValidator('login', 'required');
        $form->setValidator('login', 'email');
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                $email = strtolower($form->getValue('login'));
                $config = Config::getInstance();
                $prevStructure = $config->getStructureByName('Cabinet_User');
                $prevStructure = '0-' . $prevStructure['ID'];
                $cabinetUserModel = new Model($prevStructure);
                $response = $cabinetUserModel->recoverPassword($email);
                if (!$response['success']) {
                    echo $response['text'];
                } else {
                    $title = 'Восстановление пароля на ' . $config->domain;
                    $this->templateInit('Cabinet/Structure/User/Site/letter.twig');
                    $this->loadHelpVar();
                    $this->view->title = $title;
                    $this->view->clearPass = $response['pass'];
                    $this->view->recover = true;
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
                // Генерируем стартовую часть формы
                case false:
                    $this->templateInit('Cabinet/Structure/User/Site/recoverForm.twig');
                    $this->view->start = $form->start();
                    $formHtml = $this->view->render();
                    $form->setText($formHtml);
                    $response = $form->getText();
                    break;
            }
            return $response;
        }
    }

    /**
     * Сохранение данных о пользователе
     * @throws \Exception
     */
    public function saveAction($user = array())
    {
        $this->notPrint = true;
        $request = new Request();
        $form = new FormPhp\Forms('lkForm');
        $form->setAjaxUrl('/?mode=ajax&controller=\\\\Cabinet\\\\Structure\\\\User\\\\Site&action=save');
        $form->add('fname', 'text');
        $form->add('phone', 'text');
        $form->add('addr', 'text');
        $form->add('pass', 'text');
        $form->setValidator('phone', 'phone');
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                $db = Db::getInstance();
                $config = Config::getInstance();
                $update['fio'] = $form->getValue('fname');
                $update['phone'] = $form->getValue('phone');
                $update['address'] = $form->getValue('addr');
                $pass = $form->getValue('pass');
                if (!empty($pass)) {
                    $pass = mysqli_real_escape_string($db->getInstance(), $pass);
                    if (strlen($pass) > 0) {
                        $update['password'] = crypt($pass);
                    }
                }
                $table = $config->db['prefix'] . 'cabinet_structure_user';
                $db->update($table)->set($update)->where('ID = :ID', array('ID' => $_SESSION['login']['ID']))->exec();
                echo 'Данные сохранены';
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
                case false:
                    $this->templateInit('Cabinet/Structure/User/Site/lkForm.twig');
                    $this->view->start = $form->start();
                    $this->view->user = $user;
                    $formHtml = $this->view->render();
                    $form->setText($formHtml);
                    $response = $form->getText();
                    break;
            }
            return $response;
        }
    }

    /**
     * Проверка корректности введенного email
     *
     * @param $email
     * @return bool
     */
    protected function isEmail($email)
    {
        $result = true;
        if (function_exists('filter_var') && (!filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $result = false;
        } else {
            if (!Util::isEmail($email)) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Загрузка полученных данных
     */
    protected function loadData()
    {
        foreach ($_REQUEST as $k => $v) {
            switch (strtolower($k)) {
                case 'email':
                case 'e-mail':
                case 'login':
                    /*if (!$this->isEmail($v)) {
                        $this->answer['error'] = true;
                        $this->answer['text'] .= ' E-mail указан неверно.';
                    }
                    $this->data['email'] = strtolower($v);*/
                    break;
                case 'pass':
                case 'password':
                case 'repass':
                    if (isset($this->data['pass'])) {
                        if ($this->data['pass'] != $v) {
                            $this->answer['error'] = true;
                            $this->answer['text'] .= ' Пароли не совпадают друг с другом.';
                        }
                    } else {
                        $this->data['pass'] = $v;
                    }
                    break;
                case 'captha':
                case 'int':
                    $captcha = md5($v);
                    if ($_SESSION['cryptcode'] !== $captcha) {
                        $this->answer['text'] .= ' Не верно введена капча.';
                        $this->answer['error'] = true;
                    }
                    break;
                case 'mode':
                case 'controller':
                case 'action':
                    break;
                default:
                    $this->data[$k] = $v;
            }
        }
    }

    protected function loadHelpVar()
    {
        if (!isset($this->view)) {
            return false;
        }
        $config = Config::getInstance();
        $this->view->phone = $config->phone;
        $this->view->email = $config->mailForm;
        $this->view->domain = $config->domain;
    }

    /**
     * Генерация шаблона отображения
     *
     * @param string $tplName
     */
    public function templateInit($tplName = '')
    {
        if (!stream_resolve_include_path($tplName)) {
            echo 'Нет файла шаблона ' . $tplName;
            exit;
        }
        $tplRoot = dirname(stream_resolve_include_path($tplName));
        $tplName = basename($tplName);

        // Определяем корневую папку системы для подключение шаблонов из любой вложенной папки через их путь
        $config = Config::getInstance();
        $cmsFolder = DOCUMENT_ROOT . '/' . $config->cmsFolder;

        $folders = array_merge(array($tplRoot, $cmsFolder));
        $this->view = new \Ideal\Core\View($folders, $config->cache['templateSite']);
        $this->view->loadTemplate($tplName);
    }
}
