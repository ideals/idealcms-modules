<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Cabinet\Structure\User;

use Ideal\Core;
use Ideal\Core\Db;
use Ideal\Core\Config;

/**
 * Класс для работы со сторонними пользователями в системе
 */
class ModelAbstract extends Core\Model
{
    /** @var string Название куки, в которую запоминаем пользователя */
    protected $cookieName = 'remember';

    public function __construct($prevStructure)
    {
        parent::__construct($prevStructure);
        if (empty($this->prevStructure)) {
            $config = Config::getInstance();
            $cabinetUserConfig = $config->getStructureByName('Cabinet_User');
            if (!empty($cabinetUserConfig) && isset($cabinetUserConfig['ID'])) {
                $this->setPrevStructure('0-' . $cabinetUserConfig['ID']);
            }
        }
    }


    /**
     * Отдаёт информацию о залогиненном пользователе либо false
     *
     * @return bool|mixed
     */
    public function getLoggedUser()
    {
        // Устанавливаем сессию, если она не установлена ранее
        if (function_exists('session_status')) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
        } else {
            if (session_id() == '') {
                session_start();
            }
        }

        // Если установлены cookies, получаем данные из них
        if (isset($_COOKIE[$this->cookieName]) && empty($_SESSION['login'])) {
            $_SESSION['login'] = json_decode($_COOKIE[$this->cookieName], true);
        }

        // Если в сессии есть данные по пользователю, получаем их
        if (isset($_SESSION['login']['is_active']) && $_SESSION['login']['is_active']) {
            return $this->getUser();
        }

        return false;
    }

    /**
     * Пытается получить данные пользователя из базы
     *
     * @param string $id Значение идентификатора по которому будем искать пользователя
     * @param string $idName Название поля с индентификатором пользователя (по умолчанию - email)
     * @return mixed false или массив с данными о пользователе
     * @throws \Exception
     */
    public function getUser($id = '', $idName = 'email')
    {
        $db = Db::getInstance();
        $fields = array('table' => $this->_table);
        if (!empty($id)) {
            $par = array('id' => $id);
            $fields['idField'] = $idName;
            $_sql = "SELECT * FROM &table WHERE &idField= :id LIMIT 1";
        } else {
            $par = array('ID' => $_SESSION['login']['ID']);
            $_sql = "SELECT * FROM &table WHERE ID= :ID LIMIT 1";
        }
        $result = $db->select($_sql, $par, $fields);
        if (count($result) > 0) {

            // Если подключена структура "Order" модуля "Shop", то пытаемся получить информацию о заказах пользователя
            $config = Core\Config::getInstance();
            if ($config->getStructureByName('Shop_Order')) {
                $shopOrderTable = $config->getTableByName('Shop_Order');
                $fields = array('table' => $shopOrderTable);
                $par = array('user_id' => $_SESSION['login']['ID']);
                $_sql = "SELECT * FROM &table WHERE user_id= :user_id";
                $resultOrders = $db->select($_sql, $par, $fields);
                if (count($resultOrders) > 0) {
                    $result[0]['orders'] = $resultOrders;
                }
            }
            return $result[0];
        }
        return false;
    }

    /**
     * Авторизует пользователя в системе
     *
     * @param string $email Адрес электронной почты
     * @param string $pass Пароль
     * @return string Ответ на попытку авторизации
     */
    public function userAuthorization($email = '', $pass = '')
    {
        if (empty($email) || empty($pass)) {
            return 'Предоставлены неверные данные';
        }

        $userData = $this->getUser($email);

        if (empty($userData)) {
            return 'Пользователь с указанными данными ещё не зарегистрирован';
        }

        if (function_exists('password_verify')) {
            $trueAuth = password_verify($pass, $userData['password']);
        } else {
            $trueAuth = crypt($pass, $userData['password']) === $userData['password'];
        }

        if (!$trueAuth) {
            return 'Ошибка в логине или пароле';
        }

        $userData['is_active'] = (bool)$userData['is_active'];
        if (!$userData['is_active']) {
            return 'Пользователь с указанными данными ещё не активирован';
        }

        $_SESSION['login']['user'] = $email;
        $_SESSION['login']['ID'] = $userData['ID'];
        $_SESSION['login']['input'] = true;
        $_SESSION['login']['is_active'] = $userData['is_active'];

        if (!empty($userData['basket'])) {
            $basket = unserialize($userData['basket']);
            if ($basket->count > 0) {
                $_SESSION['login']['basket'] = json_encode($basket, JSON_FORCE_OBJECT);
            }
        }

        // Обновляем дату последнего посещения
        $db = Db::getInstance();
        $db->update($this->_table)
            ->set(array('last_visit' => time()))
            ->where('ID = :ID', array('ID' => $userData['ID']))
            ->exec();

        return 'Вы успешно вошли';
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
            $db->update($this->_table)
                ->set($update)
                ->where('ID = :ID', array('ID' => $_SESSION['login']['ID']))
                ->exec();
            $response = 'Данные сохранены';
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
    public function restorePassword($email = '')
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
                if (function_exists('password_hash')) {
                    $cryptPass = password_hash($pass, PASSWORD_DEFAULT);
                } else {
                    $cryptPass = crypt($pass);
                }
                $db->update($this->_table)->set(array('password' => $cryptPass))->where('email = :email', array
                (
                    'email' => $email
                ))->exec();
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
                        'text' => 'Такой Email уже зарегистрирован',
                        'pass' => '',
                        'key' => ''
                    );
                } else {
                    $key = md5(time());
                    $pass = $this->randPassword();
                    if (function_exists('password_hash')) {
                        $cryptPass = password_hash($pass, PASSWORD_DEFAULT);
                    } else {
                        $cryptPass = crypt($pass);
                    }
                    $db->insert($this->_table, array(
                        'email' => $userData['email'],
                        'address' => $userData['address'],
                        'phone' => $userData['phone'],
                        'password' => $cryptPass,
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
     * Завершение регистрации
     *
     * @return bool Ответ на попытку активации пользователя
     */
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
     * Выход пользователя
     */
    public static function logout()
    {
        // TODO Проверяем подключена ли структуры корзины.
        // Если корзина не пуста, то вызываем метод её сохранения для выходящего пользователя
        // и очищаем куку корзины
        // header("Set-Cookie: basket=deleted; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT");
        if (function_exists('session_status')) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
        } else {
            if (session_id() == '') {
                session_start();
            }
        }
        unset($_SESSION['login']);
    }

    /**
     * Установка имени cookie в котором хранятся данные пользователя
     *
     * @param string $cookieName
     */
    public function setCookieName($cookieName)
    {
        $this->cookieName = $cookieName;
    }
}
