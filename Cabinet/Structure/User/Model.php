<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Cabinet\Structure\User;

use Ideal\Core\Db;
use Ideal\Core;

/**
 * Класс для работы со сторонними пользователями в системе
 */
class Model extends Core\Model
{

    /**
     * Отдаёт информацию о залогиненном пользователе либо false
     *
     * @return bool|mixed
     */
    public function getLoggedUser()
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
        if (isset($_SESSION['login']['is_active']) && $_SESSION['login']['is_active']) {
            return $this->getUser();
        }
        return false;
    }

    /**
     * Пытается получить данные пользователя из базы
     *
     * @param string $email Значение электронной почты (если известно), false в противном случае
     * @return mixed false или массив с данными о пользователе
     */
    public function getUser($email = '')
    {
        $db = Db::getInstance();
        $fields = array('table' => $this->_table);
        if (!empty($email)) {
            $par = array('email' => strtolower($email));
            $_sql = "SELECT * FROM &table WHERE email= :email LIMIT 1";
        } else {
            $par = array('ID' => $_SESSION['login']['ID']);
            $_sql = "SELECT * FROM &table WHERE ID= :ID LIMIT 1";
        }
        $result = $db->select($_sql, $par, $fields);
        if (count($result) > 0) {
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
                if ($userData) {
                    if (crypt($pass, $userData['password']) === $userData['password']) {
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
                        if (!empty($userData['basket'])) {
                            $basket = unserialize($userData['basket']);
                            if ($basket->count > 0) {
                                $_SESSION['login']['basket'] = json_encode($basket, JSON_FORCE_OBJECT);
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
}
