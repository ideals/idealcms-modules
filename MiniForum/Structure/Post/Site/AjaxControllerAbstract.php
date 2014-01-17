<?php
namespace MiniForum\Structure\Post\Site;

use Ideal\Core\Util;
use Ideal\Core\Config;

class AjaxControllerAbstract extends \Ideal\Core\Site\AjaxController
{
    protected $model;
    protected $prevStructure;

    public function __construct() {
        $config = Config::getInstance();
        $forum = $config->getStructureByName('MiniForum_Post');
        $this->prevStructure = $forum['prevStructure'];
    }

    /**
     * Добавление сообщение на форум
     */
    public function insetAction() {
        $form = $_POST['form'];
        parse_str($form, $post);

        $valid = $this->validation($post);
        if ($valid !== true) {
            echo $valid;
            return;
        }

        $this->model = new Model($this->prevStructure);
        $this->model->setPost($post);
        $result = $this->model->addNewPost();

        $post['ID'] = $result;
        $this->model->sendMessages($post);

        echo $result;
        exit();

    }

    /**
     * Удаление сообщения
     */
    public function deleteAction()
    {
        $post['ID'] = $_POST['ID'];
        $post['main_parent_id'] = $_POST['main_parent_id'];
        $post['parent_id'] = $_POST['parent_id'];
        $this->model = new Model($this->prevStructure);
        $this->model->setPost($post);
        $result = $this->model->deletePost();
        if ($result) {
            echo 'Ответ успешно удалён';
        } else {
            echo 'Не удалось удалить ответ';
        }
    }

    /**
     * Подтверждение на публикацию сообщения модератором
     */
    public function moderateAction()
    {
        $post['ID'] = $_POST['ID'];
        $post['isModerated'] = $_POST['isModerated'];
        $this->model = new Model($this->prevStructure);
        $this->model->setPost($post);
        $result = $this->model->moderatedPost();
        if (!$result) {
            echo 'Не удалось выполнить действие';
        }
    }


    /**
     * Редактирование сообщения
     */
    function updateAction() {
        $form = $_POST['form'];
        parse_str($form, $post);

        $valid = $this->validation($post);
        if ($valid !== true) {
            echo $valid;
            return;
        }

        $this->model = new Model($this->prevStructure);
        $this->model->setPost($post);
        $result = $this->model->updatePost();
        if ($result) {
            echo 'Ответ успешно изменён';
        } else {
            echo 'Не удалось изменить ответ';
        }
    }

    /**
     * Вывод формы для создания темы на форуме
     */
    public function getModalFormAction() {
        parse_str($_GET['formValues'], $formValues);

        if ($formValues['ID'] !== '0') {
            $this->model = new Model($this->prevStructure);
            $post = $this->model->getPost($formValues['ID']);
            $post = $post[0];
            $formValues['content'] = $post['content'];
            $formValues['authorPF'] = $post['author'];
            $formValues['emailPF'] = $post['email'];
        }
        //$formValues['content'] = str_replace('<br />', '\r\n', $formValues['content']);
        //$formValues['content'] = str_replace('<br>', '\r\n', $formValues['content']);

        $modalForm = stream_resolve_include_path('modalForm.php');
        include($modalForm);
        exit;
    }

    /**
     * Вывод формы для ответа
     */
    public function getAnswerFormAction() {
        parse_str($_GET['formValues'], $formValues);
        if (($formValues['mainParentId'] != '0') && ($formValues['pageStructurePostId'] != '0')) {
            $goToPost = true;
        } else {
            $goToPost = false;
        }
        //parse_str($_GET['formValues'], $formValues);
        $answerForm = stream_resolve_include_path('answerForm.php');
        include($answerForm);
    }

    /**
     * @param $post
     * @return bool|string
     */
    protected function validation($post) {
        $msgValidation = '';
        foreach ($post as $key  => $value) {
            if (strlen($value) === 0) {
                $msgValidation = 'Необходимо заполнить все поля формы';
                break;
            }
        }
        if (!Util::is_email($post['email'])) {
            $msgValidation = 'Вы ввели неправильный почтовый адрес';
        }
        if (strlen($msgValidation) !== 0) {
            $msgValidation = 'MSG_Validation:' . $msgValidation;
            return $msgValidation;
        } else {
            return true;
        }
    }
}
