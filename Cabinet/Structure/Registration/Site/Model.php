<?php

namespace Cabinet\Structure\Registration\Site;

use Ideal\Core\Db;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{

    public function reg($email, $key)
    {
        $table = $this->_table;

        $db = Db::getInstance();
        $_sql = "SELECT password, act_key FROM {$table} WHERE email='{$email}' LIMIT 1";
        $arr = $db->queryArray($_sql);
        $pass = $arr[0]['password'];

        if (count($arr) == 1) {
            if ($arr[0]['act_key'] != $key) {
                if(!$arr[0]['act_key']) return 'Вы уже активировали свой аккаунт';
                return 'Ошибка в проверочном коде';
            } else {
                if ($arr[0]['act_key'])
                    $pass = crypt($pass);
                $_sql = "UPDATE {$table} SET is_active = '1', act_key = NULL, password='{$pass}' WHERE email='{$email}';";
                $db->query($_sql);
            }
        } else {
            return 'Ошибка!';
        }
        return 'Вы успешно зарегистрированы';
    }

}