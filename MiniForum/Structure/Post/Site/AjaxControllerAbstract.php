<?php
namespace MiniForum\Structure\Post\Site;

use Ideal\Core\Util;
use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Structure\User;

class AjaxControllerAbstract extends \Ideal\Core\AjaxController
{
    protected $model;
    protected $prevStructure;

    public function __construct()
    {
        $config = Config::getInstance();
        $this->prevStructure = $this->getForumInPart();
    }

    protected function getForumInPart()
    {
        $config = Config::getInstance();
        $partTable = $config->db['prefix'] . 'ideal_structure_part';
        $_sql = "SELECT ID, prev_structure FROM {$partTable} WHERE structure = 'MiniForum_Post'";
        $db = Db::getInstance();
        $forum = $db->select($_sql);

        if (!isset($forum[0]['prev_structure'])) {
            return '';
        }
        $part_prev = explode('-', $forum[0]['prev_structure']);
        return end($part_prev) . '-' . $forum[0]['ID'];
    }

    /**
     * Добавление сообщения на форум
     */
    public function insetAction()
    {
        parse_str($_POST['form'], $post);
        foreach ($post as $k => $v) {
            $post[$k] = htmlspecialchars($v);
        }


        $user = User\Model::getInstance();
        if (($post['email'] == '') && (isset($user->data['email']))) {
            $post['email'] = $user->data['email'];
        }

        $valid = $this->validation($post);
        if ($valid !== true) {
            echo $valid;
            exit();
        }

        if ($post['email'] == '') {
            $post['get_mail'] = false;
        } else {
            $post['get_mail'] = true;
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
        $post['ID'] = htmlspecialchars($_POST['ID']);
        $post['main_parent_id'] = htmlspecialchars($_POST['main_parent_id']);
        $post['parent_id'] = htmlspecialchars($_POST['parent_id']);
        $this->model = new Model($this->prevStructure);
        $this->model->setPost($post);
        $result = $this->model->deletePost();
        if ($result) {
            exit('Ответ успешно удалён');
        } else {
            exit('Не удалось удалить ответ');
        }
    }

    /**
     * Подтверждение на публикацию сообщения модератором
     */
    public function moderateAction()
    {
        $post['ID'] = htmlspecialchars($_POST['ID']);
        $post['isModerated'] = htmlspecialchars($_POST['isModerated']);
        $this->model = new Model($this->prevStructure);
        $this->model->setPost($post);
        $result = $this->model->moderatedPost();
        if (!$result) {
            exit('Не удалось выполнить действие');
        }
        exit;
    }


    /**
     * Редактирование сообщения
     */
    public function updateAction()
    {
        $form = htmlspecialchars($_POST['form']);
        parse_str($form, $post);

        $user = User\Model::getInstance();
        if (($post['email'] == '') && (isset($user->data['email']))) {
            $post['email'] = $user->data['email'];
        }

        $valid = $this->validation($post);
        if ($valid !== true) {
            echo $valid;
            return;
        }

        $this->model = new Model($this->prevStructure);
        $this->model->setPost($post);
        $result = $this->model->updatePost();
        if ($result) {
            exit('Ответ успешно изменён');
        } else {
            exit('Не удалось изменить ответ');
        }
    }

    /**
     * Вывод формы для создания темы на форуме
     */
    public function getModalFormAction()
    {
        parse_str($_POST['formValues'], $formValues);

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
    public function getAnswerFormAction()
    {
        parse_str($_POST['formValues'], $formValues);
        $formValues = array_map('htmlspecialchars', $formValues);
        if ((isset($formValues['mainParentId']) && ($formValues['mainParentId'] != '0'))
            && (isset($formValues['pageStructurePostId']) && ($formValues['pageStructurePostId'] != '0'))) {
            $goToPost = true;
        } else {
            $goToPost = false;
        }
        //parse_str($_GET['formValues'], $formValues);
        $answerForm = stream_resolve_include_path('answerForm.php');
        include($answerForm);
        exit;
    }

    /**
     * @param $post
     * @return bool|string
     */
    protected function validation($post)
    {
        $msgValidation = '';
        if ((strlen($post['author']) === 0) || (strlen($post['content']) === 0)) {
            $msgValidation = 'Необходимо заполнить все поля формы';
        }
        if ((strlen($post['email']) != 0) && (!Util::isEmail($post['email']))) {
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
