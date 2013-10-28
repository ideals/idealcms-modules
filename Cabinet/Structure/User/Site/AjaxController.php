<?php
namespace Cabinet\Structure\User\Site;

use Ideal\Core\Config;
use Ideal\Core\Db;

class AjaxController extends \Ideal\Core\Site\AjaxController
{

    public function loginAction()
    {
        $answer = array();
        session_start();
        if ($_SESSION['login']['input']) return;

        $email = @ htmlspecialchars($_POST['email']);
        $pass = @ htmlspecialchars($_POST['pass']);

        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'cabinet_structure_user';

        $tmp = $db->queryArray("SELECT ID,password,last_visit,is_active FROM {$table} WHERE email='{$email}' LIMIT 1");

        $pass = crypt($pass, $tmp[0]['password']);
        if ($pass === $tmp[0]['password']) {
            $_SESSION['login']['user'] = $email;
            $_SESSION['login']['ID'] = $tmp[0]['ID'];
            if ($tmp[0]['is_active'] == 1) {
                $_SESSION['login']['input'] = true;
            } else {
                $_SESSION['login']['input'] = 2;
            }
            $answer['text'] = 'Вы успешно вошли';
            $answer['error'] = 0;
        } else {
            $answer['text'] = 'Ошибочка вышла';
            $answer['error'] = 1;
        }
        echo json_encode($answer);
    }

    public function registrationAction()
    {
        $answer = array();
        $answer['error'] = 0;
        $answer['text'] = '';

        $fio = @ htmlspecialchars($_POST['fio']);
        $phone = @ htmlspecialchars($_POST['phone']);
        $email = @ htmlspecialchars($_POST['email']);
        $pass = @ htmlspecialchars($_POST['pass']);
        $mess = @ htmlspecialchars($_POST['mess']);

        // Check e-mail
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $answer['text'] .= "Не верный E-mail\n";
            $answer['error'] = 1;
        }

        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'cabinet_structure_user';
        $tmp = $db->queryArray("SELECT ID FROM {$table} WHERE email='{$_POST['email']}' LIMIT 1");
        if (count($tmp) > 0) {
            $answer['text'] = 'Такой Email зарегестрирован';
            $answer['error'] = 1;
        } else {
            $structure_path = $config->getStructureByName('Cabinet_User');
            $structure_path = $structure_path['ID'];

            $key = md5(time());
            $db->insert($config->db['prefix'] . 'cabinet_structure_user', array(
                'email' => $_POST['email'],
                'message' => $mess,
                'password' => $_POST['pass'],
                'fio' => $fio,
                'phone' => $phone,
                'is_active' => 0,
                'structure_path' => $structure_path,
                'act_key' => $key,
                'reg_date' => time()
            ));
            $message = <<<EOT
Для продолжение регистрации перейдите по ссылке http://{$config->domain}/cabinet.html?email=$email&key=$key

Имя: $fio
Телефон: $phone
Email: $email
Пароль: $pass
Сообщение: $mess
EOT;

            $title = 'Регистрация';
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

        print json_encode($answer);

    }

}
