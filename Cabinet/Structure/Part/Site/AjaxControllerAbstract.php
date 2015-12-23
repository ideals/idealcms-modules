<?php
namespace Cabinet\Structure\Part\Site;

use Ideal\Core\Config;
use Ideal\Core\Request;
use Cabinet\Structure\User;

class AjaxControllerAbstract extends \Ideal\Core\AjaxController
{
    /** @var array Основные параметры при ответе для json */
    protected $answer;

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
     * Авторизация
     *
     * @throws \Exception
     */
    public function loginAction()
    {
        $this->notPrint = true;
        $config = Config::getInstance();
        $prevStructure = $config->getStructureByName('Cabinet_Part');
        $prevStructure = '0-' . $prevStructure['ID'];
        $this->model = new Model($prevStructure);
        $form = $this->model->getLoginFormObject();
        $request = new Request();
        if ($form->isPostRequest()) {
            if ($form->isValid()) {
                $email = strtolower($form->getValue('login'));
                $pass = htmlspecialchars($form->getValue('pass'));
                $userModel = new User\Model('');
                $response = $userModel->userAuthorization($email, $pass);
                echo $response;
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
            }
            return $response;
        }
    }
}
