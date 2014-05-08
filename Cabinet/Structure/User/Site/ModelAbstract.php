<?php
namespace Cabinet\Structure\User\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;

class ModelAbstract extends \Ideal\Structure\Part\Site\Model
{
    protected function getWhere($where)
    {
        $where = $where;
        if (count($this->path) > 0) {
            // Считываем все элементы последнего уровня из пути
            $c = count($this->path);
            $end = end($this->path);
        }

        if (is_array($end)) {
            $where .= " AND is_active=1";
        }

        if ($where != '') {
            $where = 'WHERE ' . $where;
        }

        return $where;
    }

    public function reg($email, $key)
    {
        $table = $this->_table;
        $config = Config::getInstance();

        $db = Db::getInstance();
        $_sql = "SELECT fio, phone, password, act_key,message FROM {$table} WHERE email='{$email}' LIMIT 1";
        $arr = $db->queryArray($_sql);
        $pass = $arr[0]['password'];

        if (count($arr) == 1) {
            if ($arr[0]['act_key'] != $key) {
                if ($arr[0]['act_key']) {
                    return 'Ошибка в проверочном коде';
                }
                header('Refresh: 2; url=/');
            } else {
                if ($arr[0]['act_key']) {
                    $pass = crypt($pass);
                }
                $_sql = "UPDATE {$table} SET act_key = NULL, password='{$pass}' WHERE email='{$email}';";
                $db->query($_sql);
                $message = <<<EOT
Новый пользователь

Имя: {$arr[0]['fio']}
Телефон: {$arr[0]['phone']}
Email: $email
Сообщение: {$arr[0]['message']}
EOT;

                $title = 'Регистрация';
                $to = $config->mailForm;
                $headers = "From: {$config->robotEmail}\r\n"
                    . "Content-type: text/plain; charset=\"utf-8\"";
                mail($to, $title, $message, $headers);
            }
        } else {
            return 'Ошибка! Такой E-mail не зарегестрирован.';
        }
        return 'Вы успешно зарегистрированы';
    }

    /**
     * Функция используеться если подключен модуль Shop_Order
     * @param $arr
     */
    public function regUserFromOrder($arr)
    {
        $table = $this->_table;
        $db = Db::getInstance();
        $config = Config::getInstance();
        $_sql = "SELECT ID FROM {$table} WHERE email = '{$arr['email']}' LIMIT 1";
        $result = $db->queryArray($_sql);
        if (count($result) == 1) {
            return false;
        }
        $password = $this::randPassword();
        $arr['password'] = crypt($password);
        $message = <<<EOT
Новый пользователь

Имя: {$arr['fio']}
Телефон: {$arr['phone']}
Email: {$arr['email']}
Пароль: {$password}
EOT;
        $title = 'Регистрация на ' . $config->domain;
        $to = $config->mailForm;
        $headers = "From: {$config->robotEmail}\r\n"
            . "Content-type: text/plain; charset=\"utf-8\"";
        mail($to, $title, $message, $headers);
        $db->insert($table, $arr);

    }

    public function finishReg()
    {
        $db = Db::getInstance();
        $confing = Config::getInstance();
        $email = mysql_real_escape_string($_GET['email']);
        $key = mysql_real_escape_string($_GET['key']);
        $_sql = "SELECT * FROM {$this->_table} WHERE email='{$email}' AND act_key='{$key}' LIMIT 1";
        $result = $db->queryArray($_sql);
        if (count($result) == 1) {
            $_sql = "UPDATE {$this->_table} SET is_active=1, act_key=''";
            $db->query($_sql);
            return true;
        }
        return false;
    }

    public function getUser($email = false)
    {
        $db = Db::getInstance();
        if ($email !== false) {
            $where = 'email=\'' . strtolower($email) . "'";
        } else {
            $where = 'ID=' . $_SESSION['login']['ID'];
        }
        $_sql = "SELECT email, address, fio, phone FROM {$this->_table} WHERE {$where} LIMIT 1";
        $result = $db->queryArray($_sql);
        return $result[0];
    }

    public function getStructureElements()
    {
    }

    /**
     * Генерация пароля
     * @param int $min Минимальное количество в пароле
     * @param int $max Максимальное количество в пароле
     * @return string
     */
    static public function randPassword($min = 8, $max = 12)
    {
        $length = rand($min, $max);
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return substr(str_shuffle($chars), 0, $length);
    }

}
