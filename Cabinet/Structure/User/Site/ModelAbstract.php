<?php
namespace Cabinet\Structure\User\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Field;

class ModelAbstract extends \Ideal\Structure\Part\Site\Model
{
    /** @var int Количество найденных в базе пользователей по предоставленным данным */
    protected $userCount = 1;

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

    /**
     * Регистрация нового пользователя в системе
     *
     * @param string $email Электронная почта
     * @param string $key Ключ
     *
     * @return string Ответ
     * @deprecated
     */
    public function reg($email, $key)
    {
        // TODO Узнать используется ли данный метод хоть где-нибудь хотябы на каком-нибудь проекте?
        $table = $this->_table;
        $config = Config::getInstance();

        $db = Db::getInstance();
        $_sql = "SELECT fio, phone, password, act_key,message FROM {$table} WHERE email='{$email}' LIMIT 1";
        $arr = $db->select($_sql);
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
     * Регистрация нового пользователя в системе
     *
     * @param array $userData Данные пользователя желающего зарегистрироваться
     *
     * @return array Ответ на попытку региистрации в системе, а так же пароль сгенерированны для нового пользователя и ключ авторизации
     */
    public function userRegistration(array $userData)
    {
        $response = array(
            'success' => false,
            'text' => 'Нет данных о новом пользователе',
            'pass' => '',
            'key' => ''
        );
        if (!empty($userData)) {
            if (!isset($userData['email']) || !isset($userData['address']) || !isset($userData['phone']) || !isset($userData['fio'])) {
                $response = array(
                    'success' => false,
                    'text' => 'Недостаточно данных для регистрации нового пользователя',
                    'pass' => '',
                    'key' => ''
                );
            } else {
                $db = Db::getInstance();
                $par = array('email' => strtolower($userData['email']));
                $fields = array('table' => $this->_table);
                $tmp = $db->select("SELECT ID FROM &table WHERE email= :email LIMIT 1", $par, $fields);
                if (count($tmp) > 0) {
                    $response = array(
                        'success' => false,
                        'text' => 'Такой Email уже зарегестрирован',
                        'pass' => '',
                        'key' => ''
                    );
                } else {
                    $key = md5(time());
                    $pass = $this->randPassword();
                    $db->insert($this->_table, array(
                        'email' => $userData['email'],
                        'address' => $userData['address'],
                        'phone' => $userData['phone'],
                        'password' => $pass,
                        'fio' => $userData['fio'],
                        'is_active' => 0,
                        'prev_structure' => $this->getPrevStructure(),
                        'act_key' => $key,
                        'reg_date' => time()
                    ));

                    $response = array(
                        'success' => true,
                        'text' => 'Пользователь успешно зарегистрирован',
                        'pass' => $pass,
                        'key' => $key
                    );
                }
            }
        }
        return $response;
    }

    /**
     * Восстановление пароля
     *
     * @param string $email Электронная почта
     *
     * @return array Ответ на попытку восстановления пароля, а так же новый сгенерированны пароль для пользователя
     */
    public function recoverPassword($email = '')
    {
        $response = array(
            'success' => false,
            'text' => 'Не известно для кого восстанавливать пароль',
            'pass' => '',
        );
        if (!empty($email)) {
            $db = Db::getInstance();
            $par = array('email' => $email);
            $fields = array('table' => $this->_table);
            $user = $db->select("SELECT ID FROM &table WHERE email= :email LIMIT 1", $par, $fields);
            if (count($user) == 0) {
                $response = array(
                    'success' => false,
                    'text' => 'Данный E-mail еще не зарегистрирован',
                    'pass' => '',
                    'key' => ''
                );
            } else {
                $pass = $this->randPassword();
                $db->update($this->_table)->set(array('password' => crypt($pass)))->where('email = :email', array
                ('email' => $email))->exec();
                $response = array(
                    'success' => true,
                    'text' => 'Пароль обновлён',
                    'pass' => $pass,
                );
            }
        }
        return $response;
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
        $result = $db->select($_sql);
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
        $to = $arr['email'];
        $headers = "From: {$config->robotEmail}\r\n"
            . "Content-type: text/plain; charset=\"utf-8\"";
        mail($to, $title, $message, $headers);
        $db->insert($table, $arr);

    }

    public function finishReg()
    {
        $db = Db::getInstance();
        $email = mysqli_real_escape_string($db, $_GET['email']);
        $key = mysqli_real_escape_string($db, $_GET['key']);
        $_sql = "SELECT * FROM {$this->_table} WHERE email='{$email}' AND act_key='{$key}' LIMIT 1";
        $result = $db->select($_sql);
        if (count($result) == 1) {
            $_sql = "UPDATE {$this->_table} SET is_active=1, act_key=''";
            $db->query($_sql);
            return true;
        }
        return false;
    }

    /**
     * Пытается получить данные пользователя из базы
     *
     * @param mixed $email Значение электронной почты (если известно), false в противном случае
     * @return mixed Null или массив с данными о пользователе
     */
    public function getUser($email = false)
    {
        $db = Db::getInstance();

        $fields = array('table' => $this->_table);

        if ($email !== false) {
            $par = array('email' => strtolower($email));
            $_sql = "SELECT * FROM &table WHERE email= :email LIMIT 1";
        } else {
            $par = array('ID' => $_SESSION['login']['ID']);
            $_sql = "SELECT * FROM &table WHERE ID= :ID LIMIT 1";
        }
        $result = $db->select($_sql, $par, $fields);
        $this->userCount = count($result);
        return $result[0];
    }

    /**
     * Заносит данные авторизованного пользователя в сессию иначе отдаёт сообщение о неудачной авторизации
     *
     * @param string $email Электронная почта
     * @param string $pass Пароль
     *
     * @return string Ответ на попытку войти в систему
     */
    public function userAuthorization($email = '', $pass = '')
    {
        $response = 'Предоставлены не верные данные';
        if (!empty($email) && !empty($pass)) {
            $firstStepCheck = true;
            if (isset($_SESSION['login']['input']) && $_SESSION['login']['input']) {
                if ($email != $_SESSION['login']['user']) {
                    $response = 'Пользователь с указанными данными ещё не зарегистрирован';
                    $firstStepCheck = false;
                } elseif (!$_SESSION['login']['is_active']) {
                    $response = 'Пользователь с указанными данными ещё не активирован';
                    $firstStepCheck = false;
                }
            }
            if ($firstStepCheck) {
                $userData = $this->getUser($email);
                if (!empty($userData)) {
                    if (($this->userCount === 1) && (crypt($pass, $userData['password']) === $userData['password'])) {
                        $_SESSION['login']['user'] = $email;
                        $_SESSION['login']['ID'] = $userData['ID'];
                        $_SESSION['login']['input'] = true;
                        if (function_exists('boolval')) {
                            $_SESSION['login']['is_active'] = boolval($userData['is_active']);
                        } else {
                            if ($userData['is_active']) {
                                $_SESSION['login']['is_active'] = true;
                            } else {
                                $_SESSION['login']['is_active'] = false;
                            }
                        }
                        $response = 'Вы успешно вошли';
                    } else {
                        $response = 'Ошибка в логине(email) или пароле';
                    }
                } else {
                    $response = 'Пользователя с указанными данными не существует';
                }
            }
        }
        return $response;
    }

    /**
     * Сохраняет изменённые данные пользователя
     *
     * @param array $userData Данные с индивидуальной страницы пользователя
     *
     * @return string Ответ на попытку сохранения данных о пользователе
     */
    public function saveUserData(array $userData)
    {
        $response = 'Предоставлены не верные данные';
        if (!empty($userData)) {
            $update = array_filter($userData);
            $db = Db::getInstance();
            if (isset($update['password'])) {
                $update['password'] = $db->real_escape_string($update['password']);
                if (strlen($update['password']) > 0) {
                    $update['password'] = crypt($update['password']);
                }
            }
            $db->update($this->_table)->set($update)->where('ID = :ID', array('ID' => $_SESSION['login']['ID']))->exec();
            $response = 'Данные сохранены';
        }
        return $response;
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

    /**
     * Генерация абсолютного пути до страницы логина/регистрации/подтверждения
     *
     * @return string Абсолютный путь до страницы логина/регистрации/подтверждения
     */
    public function getFullUrl()
    {
        $pageData = $this->getPageData();
        $url = new Field\Url\Model();
        $link = $url->getUrl($pageData);
        return $link;
    }

    /**
     * Сохраняет текущее состояние корзины для активного пользователя
     */
    public function saveBasket()
    {
        if (isset($_SESSION['login']['ID'])) {
            $userId = $_SESSION['login']['ID'];
        } else {
            $userId = 0;
        }
        if (isset($_COOKIE['basket'])) {
            $basket = serialize(json_decode($_COOKIE['basket']));
        } else {
            $basket = '';
        }
        if ($userId) {
            $db = Db::getInstance();
            $db->update($this->_table)
                ->set(array('basket' => $basket))
                ->where('ID = :ID', array('ID' => $userId))
                ->exec();
        }
    }
}
