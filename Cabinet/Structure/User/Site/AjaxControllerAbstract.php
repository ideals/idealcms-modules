<?php
namespace Cabinet\Structure\User\Site;

use Ideal\Core\Config;
use Ideal\Core\Db;

class AjaxControllerAbstract extends \Ideal\Core\Site\AjaxController
{
    /**
     * TODO
     * Нужна ли после регистрации активация через e-mail
     * @var bool
     */
    protected $needActive = true;

    public function loginAction()
    {
        session_start();
        $answer = array('error' => false, 'text' => '', 'refresh' => false);
        if (isset($_SESSION['login']['input']) && $_SESSION['login']['input']) return;
        if (!isset($_POST['login']) || !isset($_POST['pass'])) {
            $answer['text'] = 'Вы указали не все данные';
            $answer['error'] = true;
        }

        if (!$answer['error']) {
            $email = @ htmlspecialchars($_POST['login']);
            $pass = @ htmlspecialchars($_POST['pass']);

            $db = Db::getInstance();
            $config = Config::getInstance();
            $table = $config->db['prefix'] . 'cabinet_structure_user';

            $tmp = $db->queryArray("SELECT ID,password,last_visit,is_active FROM {$table} WHERE email='{$email}' LIMIT 1");

            //$pass = crypt($pass, $tmp[0]['password']);
            if (count($tmp) === 1 && crypt($pass, $tmp[0]['password']) === $tmp[0]['password']) {
                $_SESSION['login']['user'] = $email;
                $_SESSION['login']['ID'] = $tmp[0]['ID'];
                $_SESSION['login']['input'] = true;
                if ($tmp[0]['is_active'] == 1) {
                    $_SESSION['login']['is_active'] = true;
                } else {
                    $_SESSION['login']['is_active'] = false;
                }
                $answer['text'] = 'Вы успешно вошли';
                $answer['refresh'] = true;
            } else {
                $answer['text'] = 'Ошибка в логине(email) или пароле';
                $answer['error'] = true;
            }
        }
        echo json_encode($answer);
        exit;
    }

    /**
     * Регистрация пользователя
     */
    public function registrationAction()
    {
        $answer = array('error' => false, 'text' => '', 'refresh' => false);
        session_start();

        // Получение данных из формы регистрации
        $name = @ htmlspecialchars($_POST['name']);
        $lastName = @ htmlspecialchars($_POST['lastname']);
        $phone = @ htmlspecialchars($_POST['phone']);
        $address = @ htmlspecialchars($_POST['addr']);
        $captcha = @ htmlspecialchars($_POST['int']);
        $captcha = md5($captcha);
        $fio = $name . ' ' . $lastName;

        if ($_SESSION['cryptcode'] !== $captcha) {
            $answer['text'] = 'Не верно введена капча';
            $answer['error'] = true;
        }

        if (!$answer['error']) {
            $config = Config::getInstance();
            $db = Db::getInstance();
            // Создаем пароль
            $clearPass = $this->randPassword();
            // Хешируем пароль
            $pass = crypt($clearPass);
            $email = mysql_real_escape_string($_POST['email']);
            // Установка таблицы в базе данных
            $table = $config->db['prefix'] . 'cabinet_structure_user';
            // Проверка на существование в базе данных email
            $tmp = $db->queryArray("SELECT ID FROM {$table} WHERE email='{$email}' LIMIT 1");
            if (count($tmp) > 0) {
                $answer['text'] = 'Такой Email уже зарегестрирован';
                $answer['error'] = true;
            } else {
                // Определяем с какого url был переход
                // Что бы потом можно было указать страницу для заверщения регистрации
                $url = array();
                $patter = "/{$config->domain}(.*)\?/iU";
                preg_match($patter, $_SERVER['HTTP_REFERER'], $url);
                $url = end($url);
                $url = trim($url, '/');
                $url = $url . $config->urlSuffix;
                // Определние правильного prevStructure при помощи конфига
                $prevStructure = $config->getStructureByName('Cabinet_User');
                $prevStructure = '0-' . $prevStructure['ID'];

                // Ключ который высылается пользователю для подтверждения почты и активации пользователя
                $key = md5(time());
                $db->insert($config->db['prefix'] . 'cabinet_structure_user', array(
                    'email' => $_POST['email'],
                    'address' => $address,
                    'phone' => $phone,
                    'password' => $pass,
                    'fio' => $fio,
                    'is_active' => 0,
                    'prev_structure' => $prevStructure,
                    'act_key' => $key,
                    'reg_date' => time()
                ));
                // Текст письма которое получает пользователь на почту
                $message = <<<EOT
Для продолжение регистрации перейдите по ссылке http://{$config->domain}/{$url}?action=finishReg&email={$email}&key={$key}

Имя: {$fio}
Email: $email
Пароль: {$clearPass}
EOT;

                $title = 'Регистрация' . $config->domain;
                $to = $email;
                $headers = "From: {$config->robotEmail}\r\n"
                    . "Content-type: text/plain; charset=\"utf-8\"";
                if (mail($to, $title, $message, $headers)) {
                    $answer['text'] = 'Вам было отправлено письмо с инструкцией для дальнейшей регистрации';
                } else {
                    $answer['text'] = 'Ошибка. Попробуйте чуть позже';
                    $answer['error'] = 1;
                }
            }
        }


        print json_encode($answer);
        exit;
    }

    public function recoverAction()
    {
        $answer = array('error' => false, 'text' => '', 'refresh' => false);
        $db = Db::getInstance();
        $config = Config::getInstance();
        $email = mysql_real_escape_string($_POST['login']);
        if (strlen($email) < 1 || strpos($email, '@') == false) {
            $answer['text'] = 'Не указан E-mail';
            $answer['error'] = true;
            print json_encode($answer);
            exit;
        }
        $table = $config->db['prefix'] . 'cabinet_structure_user';
        $_sql = "SELECT ID FROM {$table} WHERE email='{$email}' LIMIT 1";
        $user = $db->queryArray($_sql);
        if (count($user) == 0) {
            $answer['error'] = true;
            $answer['text'] = 'Данный E-mail еще не зарегистрирован';
        }
        if (!$answer['error']) {
            $clearPass = $this->randPassword();
            $pass = crypt($clearPass);
            $title = 'Восстановление пароля на ' . $config->domain;
            $headers = "From: {$config->robotEmail}\r\n"
                . "Content-type: text/plain; charset=\"utf-8\"";
            $to = $email;
            $msg = <<<EOT
Ваш новый пароль:{$clearPass}
EOT;
            if (mail($to, $title, $msg, $headers)) {
                $answer['text'] = 'Вам было отправлено письмо с новым паролем';
            } else {
                $answer['text'] = 'Ошибка. Попробуйте чуть позже';
                $answer['error'] = 1;
            }
            $_sql = "UPDATE {$table} SET password='{$pass}' WHERE email='{$email}'";
            $db->query($_sql);
        }

        print json_encode($answer);
        exit;
    }

    public function saveAction()
    {
        $answer = array('error' => false, 'text' => '', 'refresh' => false);
        session_start();
        $update['fio'] = @ htmlspecialchars($_POST['fname']);
        $update['phone'] = @ htmlspecialchars($_POST['phone']);
        $update['address'] = @ htmlspecialchars($_POST['addr']);
        $pass = mysql_real_escape_string($_POST['pass']);
        if (strlen($pass) > 0) {
            $update['password'] = crypt($pass);
        }

        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'cabinet_structure_user';
        $db->update($table, $_SESSION['login']['ID'], $update);
        $answer['text'] = 'Данные сохранены';

        print json_encode($answer);
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
}
