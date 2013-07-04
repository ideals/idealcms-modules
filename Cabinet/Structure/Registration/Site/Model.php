<?php

namespace Cabinet\Structure\Registration\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{

    public function reg($email, $key)
    {
        $table = $this->_table;
        $config = Config::getInstance();

        $db = Db::getInstance();
        $_sql = "SELECT fio, phone, password, act_key FROM {$table} WHERE email='{$email}' LIMIT 1";
        $arr = $db->queryArray($_sql);
        $pass = $arr[0]['password'];

        if (count($arr) == 1) {
            if ($arr[0]['act_key'] != $key) {
                if ($arr[0]['act_key']) return 'Ошибка в проверочном коде';
                header('Refresh: 2; url=/');
            } else {
                if ($arr[0]['act_key'])
                    $pass = crypt($pass);
                $_sql = "UPDATE {$table} SET act_key = NULL, password='{$pass}' WHERE email='{$email}';";
                $db->query($_sql);
                $message = <<<EOT
Новый пользователь

Имя: {$arr[0]['fio']}
Телефон: {$arr[0]['phone']}
Email: $email
EOT;

                $title = 'Регистрация';
                $to = $config->mailForm;
                $headers = "From: {$config->robotEmail}\r\n"
                    . "Content-type: text/plain; charset=\"utf-8\"";
                if (mail($to, $title, $message, $headers)) ;
            }
        } else {
            return 'Ошибка! Такой E-mail не зарегестрирован.';
        }
        return 'Вы успешно зарегистрированы';
    }

}