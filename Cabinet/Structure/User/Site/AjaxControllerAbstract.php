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
    public function registrationAction($link = '')
    {
        $this->notPrint = true;
        $request = new Request();
        $form = new FormPhp\Forms('registrationForm');
        $form->setAjaxUrl('/?mode=ajax&controller=\\\\Cabinet\\\\Structure\\\\User\\\\Site&action=registration');
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
                $fio = $form->getValue('lastname') . ' ' . $form->getValue('name');
                $phone = $form->getValue('phone');
                $address = $form->getValue('addr');
                $email = $form->getValue('email');
                $clearPass = $this->randPassword();
                $pass = crypt($clearPass);
                $config = Config::getInstance();
                $db = Db::getInstance();

                // Установка таблицы в базе данных
                $table = $config->db['prefix'] . 'cabinet_structure_user';

                $par = array('email' => strtolower($email));
                $fields = array('table' => $table);
                $tmp = $db->select("SELECT ID FROM &table WHERE email= :email LIMIT 1", $par, $fields);
                if (count($tmp) > 0) {
                    echo ' Такой Email уже зарегестрирован.';
                } else {
                    $prevStructure = $config->getStructureByName('Cabinet_User');
                    $prevStructure = '0-' . $prevStructure['ID'];
                    $key = md5(time());
                    $db->insert($table, array(
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
                    $link = 'http://' . $config->domain . $form->getValue('link') . '?';
                    $link .= 'action=finishReg';
                    $link .= '&email=' . urlencode($email);
                    $link .= '&key=' . urlencode($key);
                    $this->view->link = $link;
                    $this->view->title = $title;
                    $msg = $this->view->render();

                    if ($form->sendMail($config->robotEmail, $email, $title, $msg, true)) {
                        echo 'Вам было отправлено письмо с инструкцией для дальнейшей регистрации';
                    } else {
                        echo 'Ошибка. Попробуйте чуть позже';
                    }

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
            $formHtml = <<<HTML
            <script type="text/javascript"
        src="/?mode=ajax&controller=\Cabinet\Structure\User\Site&action=registration&subMode=js"></script>
<link media="all" rel="stylesheet" type="text/css" href="/?mode=ajax&controller=\Cabinet\Structure\User\Site&action=registration&subMode=css"/>
{$form->start()}
                    <table>
                        <tr>
                            <td>Фамилия*:</td>
                            <td><input type="text" value="" name="lastname"></td>
                        </tr>
                        <tr>
                            <td>Имя*:</td>
                            <td><input type="text" value="" name="name"></td>
                        </tr>
                        <tr>
                            <td>Телефон*:</td>
                            <td><input type="text" value="" name="phone"></td>
                        </tr>
                        <tr>
                            <td>Адрес*:</td>
                            <td><textarea name="addr"></textarea></td>
                        </tr>
                        <tr>
                            <td>E-mail*:</td>
                            <td><input class="required" type="text" value="" name="email"></td>
                        </tr>
                        <tr>
                            <td colspan="2" align="center"><br></td>
                        </tr>
                        <tr>
                            <td>
                                <img src="/images/captcha.jpg" onclick="getCaptcha(this)"
                                     title="нажмите что бы обновить" style="cursor: pointer">
                            </td>
                            <td>
                                <input style="textalign:center;width:100px;" type="text" name="int" size="6">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" align="center">
                                <input type="hidden" value="{$link}" name="link">
                                <p style="margin: auto;">Введите защитный код с картинки</p>
                                <br><br>
                                <br><br>
                                <input type="submit" value="ЗАРЕГИСТРИРОВАТЬСЯ">
                                <br>
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
                $db = Db::getInstance();
                $config = Config::getInstance();
                $email = strtolower($form->getValue('login'));
                $table = $config->db['prefix'] . 'cabinet_structure_user';
                $par = array('email' => $email);
                $fields = array('table' => $table);
                $user = $db->select("SELECT ID FROM &table WHERE email= :email LIMIT 1", $par, $fields);
                if (count($user) == 0) {
                    echo ' Данный E-mail еще не зарегистрирован.';
                } else {
                    $clearPass = $this->randPassword();
                    $pass = crypt($clearPass);
                    $title = 'Восстановление пароля на ' . $config->domain;
                    $this->templateInit('Cabinet/Structure/User/Site/letter.twig');
                    $this->loadHelpVar();
                    $this->view->title = $title;
                    $this->view->clearPass = $clearPass;
                    $this->view->recover = true;
                    $html = $this->view->render();
                    if ($form->sendMail($config->robotEmail, $email, $title, $html, true)) {
                        $db->update($table)->set(array('password' => $pass))->where('email = :email', array('email' => $email))->exec();
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
                    $formHtml = <<<HTML
<script type="text/javascript"
        src="/?mode=ajax&controller=\Cabinet\Structure\User\Site&action=recover&subMode=js"></script>
<link media="all" rel="stylesheet" type="text/css" href="/?mode=ajax&controller=\Cabinet\Structure\User\Site&action=recover&subMode=css"/>
{$form->start()}
    <br>
                    <table>
                        <tr>
                            <td width="100">Login (email)*:</td>
                            <td><input type="text" value="" name="login"></td>
                        </tr>
                        <tr>
                            <td colspan="2"><br><br></td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <input type="submit" value="ВОССТАНОВИТЬ">
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
                    $formHtml = <<<HTML
            <script type="text/javascript"
        src="/?mode=ajax&controller=\Cabinet\Structure\User\Site&action=save&subMode=js"></script>
<link media="all" rel="stylesheet" type="text/css" href="/?mode=ajax&controller=\Cabinet\Structure\User\Site&action=save&subMode=css"/>
{$form->start()}
                    <table>
                        <tr>
                            <td>Ваше имя:</td>
                            <td><input type="text" value="{$user['fio']}" name="fname"></td>
                        </tr>
                        <tr>
                            <td>Телефон:</td>
                            <td><input type="text" value="{$user['phone']}" name="phone"></td>
                        </tr>
                        <tr>
                            <td>Адрес</td>
                            <td><textarea name="addr">{$user['address']}</textarea></td>
                        </tr>
                        <tr>
                            <td><br>Email при регистрации (login)</td>
                            <td><br>{$user['email']}</td>
                        </tr>
                        <tr>
                        </tr>
                        <tr>
                            <td colspan="2" align="center">
                                <br>
                                <hr>
                            </td>
                        </tr>
                        <tr>
                            <td>Новый пароль</td>
                            <td><input type="password" value="" name="pass"></td>
                        </tr>
                        <tr>
                            <td colspan="2" align="center">
                                Оставьте поле пустым, что бы не менять пароль
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"><br><br><br>
                                <input type="submit" value="СОХРАНИТЬ">
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
