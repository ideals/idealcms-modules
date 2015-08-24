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
                if (isset($_SESSION['login']['input']) && $_SESSION['login']['input']) {
                    exit();
                }
                $email = strtolower($form->getValue('login'));
                $pass = htmlspecialchars($form->getValue('pass'));
                $db = Db::getInstance();
                $config = Config::getInstance();
                $table = $config->db['prefix'] . 'cabinet_structure_user';


                $par = array('email' => $email);
                $fields = array('table' => $table);
                $tmp = $db->select('SELECT ID,password,last_visit,is_active FROM &table WHERE email= :email LIMIT 1', $par, $fields);

                if ((count($tmp) === 1) && (crypt($pass, $tmp[0]['password']) === $tmp[0]['password'])) {
                    $_SESSION['login']['user'] = $email;
                    $_SESSION['login']['ID'] = $tmp[0]['ID'];
                    $_SESSION['login']['input'] = true;
                    $_SESSION['login']['is_active'] = boolval($tmp[0]['is_active']);
                    echo 'Вы успешно вошли';
                } else {
                    echo 'Ошибка в логине(email) или пароле';
                }
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
                    $formHtml = <<<HTML
<script type="text/javascript"
        src="/?mode=ajax&controller=\Cabinet\Structure\User\Site&action=login&subMode=js"></script>
<link media="all" rel="stylesheet" type="text/css" href="/?mode=ajax&controller=\Cabinet\Structure\User\Site&action=login&subMode=css"/>
{$form->start()}
    <table>
      <tr>
        <td width="100px">Login (email)*</td>
        <td>
          <div>
            <input type="text" value="" name="login">
          </div>
        </td>
      </tr>
      <tr>
        <td width="100px">Пароль*</td>
        <td>
          <div>
            <input type="password" value="" name="pass">
          </div>
        </td>
      </tr>
      <tr>
        <td colspan="2"><br><br></td>
      </tr>
      <tr>
        <td colspan="2">
          <input type="submit" value="ВОЙТИ">
          <br/>
          <br/>
          <a class="submit" href="{$link}?action=rec">ВОССТАНОВИТЬ ПАРОЛЬ</a>
          <a class="submit" href="{$link}?action=reg">ЗАРЕГИСТРИРОВАТЬСЯ</a>
        </td>
      </tr>
    </table>
</form>
HTML;
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
    public function registrationAction()
    {
        // Проверка данных из формы
        if (!(isset($this->data['lastname']) && (strlen($this->data['lastname']) > 1))
            || !(isset($this->data['name']) && (strlen($this->data['name']) > 1))
            || !(isset($this->data['phone']) && (strlen($this->data['phone']) > 1))
            || !(isset($this->data['email']) && (strlen($this->data['email']) > 1))
            || !(isset($this->data['addr']) && (strlen($this->data['addr']) > 1))
        ) {
            $this->answer['error'] = true;
            $this->answer['text'] .= ' Заполнены не все поля.';
            exit();
        }
        $fio = $this->data['fio'];
        $phone = $this->data['phone'];
        $address = $this->data['addr'];
        $email = $this->data['email'];
        if (isset($data['pass']) && (strlen($this->data['pass']) > 4)) {
            $clearPass = $this->data['pass'];
        } else {
            // Создаем пароль
            $clearPass = $this->randPassword();
        }
        // Хешируем пароль
        $pass = crypt($clearPass);

        $config = Config::getInstance();
        $db = Db::getInstance();

        $email = mysqli_real_escape_string($db->getInstance(), $email);
        $email = strtolower($email);
        // Установка таблицы в базе данных
        $table = $config->db['prefix'] . 'cabinet_structure_user';
        // Проверка на существование в базе данных email
        $tmp = $db->select("SELECT ID FROM {$table} WHERE email='{$email}' LIMIT 1");
        if (count($tmp) > 0) {
            $this->answer['text'] .= ' Такой Email уже зарегестрирован.';
            $this->answer['error'] = true;
            exit();
        }
        // Определние правильного prevStructure при помощи конфига
        $prevStructure = $config->getStructureByName('Cabinet_User');
        $prevStructure = '0-' . $prevStructure['ID'];

        // Ключ который высылается пользователю для подтверждения почты и активации пользователя
        $key = md5(time());
        $db->insert($config->db['prefix'] . 'cabinet_structure_user', array(
            'email' => $email,
            'address' => $address,
            'phone' => $phone,
            'password' => $pass,
            'fio' => $fio,
            'is_active' => 0,
            'prev_structure' => $prevStructure,
            'act_key' => $key,
            'reg_date' => time()
        ));
        $title = 'Регистрация на ' . $config->domain;

        $this->templateInit('Cabinet/Structure/User/Site/letter.twig');
        $this->loadHelpVar();

        $this->view->reg = true;
        $this->view->fio = $fio;
        $this->view->email = $email;
        $this->view->pass = $clearPass;
        $link = 'http://' . $config->domain . $this->data['link'] . '?';
        $link .= 'action=finishReg';
        $link .= '&email=' . urlencode($email);
        $link .= '&key=' . urlencode($key);
        $this->view->link = $link;
        $this->view->title = $title;

        $msg = $this->view->render();

        $mail = new Sender();
        $mail->setSubj($title);
        $mail->setHtmlBody($msg);

        if ($mail->sent($config->robotEmail, $this->data['email'])) {
            $this->answer['text'] = 'Вам было отправлено письмо с инструкцией для дальнейшей регистрации';
        } else {
            $this->answer['text'] = 'Ошибка. Попробуйте чуть позже';
            $this->answer['error'] = 1;
        }
        exit();
    }

    /**
     * Восстановление пароля
     * @throws \Exception
     */
    public function recoverAction()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $email = mysqli_real_escape_string($db->getInstance(), $this->data['email']);
        $email = strtolower($email);
        $table = $config->db['prefix'] . 'cabinet_structure_user';
        $_sql = "SELECT ID FROM {$table} WHERE email='{$email}' LIMIT 1";
        $user = $db->select($_sql);
        if (count($user) == 0) {
            $this->answer['error'] = true;
            $this->answer['text'] .= ' Данный E-mail еще не зарегистрирован.';
            exit();
        }
        $clearPass = $this->randPassword();
        $pass = crypt($clearPass);
        $mail = new Sender();
        $title = 'Восстановление пароля на ' . $config->domain;
        $mail->setSubj($title);
        $this->templateInit('Cabinet/Structure/User/Site/letter.twig');
        $this->loadHelpVar();

        $this->view->title = $title;
        $this->view->clearPass = $clearPass;
        $this->view->recover = true;

        $html = $this->view->render();
        $mail->setHtmlBody($html);
        if ($mail->sent($config->robotEmail, $this->data['email'])) {
            $_sql = "UPDATE {$table} SET password='{$pass}' WHERE email='{$email}'";
            $db->query($_sql);
            $this->answer['text'] .= ' Вам выслан новый пароль.';
        } else {
            $this->answer['error'] = true;
            $this->answer['text'] .= ' Услуга временно недоступна попробуйте позже.';
        }
        exit();
    }

    /**
     * Сохранение данных о пользователе
     * @throws \Exception
     */
    public function saveAction()
    {
        session_start();
        $update['fio'] = @ htmlspecialchars($_POST['name']);
        $update['phone'] = @ htmlspecialchars($_POST['phone']);
        $update['address'] = @ htmlspecialchars($_POST['address']);
        $update['city'] = @ htmlspecialchars($_POST['city']);
        $update['postcode'] = @ htmlspecialchars($_POST['postcode']);
        if (isset($_POST['password'])) {
            $pass = mysql_real_escape_string($_POST['password']);
            if (strlen($pass) > 0) {
                $update['password'] = crypt($pass);
            }
        }

        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'cabinet_structure_user';
        $db->update($table, $_SESSION['login']['ID'], $update);
        $this->answer['text'] = 'Данные сохранены';

        print json_encode($this->answer);
        exit;
    }

    /**
     * Генерация пароля
     * @param int $min Минимальное количество в пароле
     * @param int $max Максимальное количество в пароле
     * @return string
     */
    protected function randPassword($min = 8, $max = 12)
    {
        $length = rand($min, $max);
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return substr(str_shuffle($chars), 0, $length);
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
