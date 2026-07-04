<?php

namespace MiniForum\Structure\Post\Site;

use Ideal\Core\AjaxController;
use Ideal\Core\Util;
use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Structure\User;

class AjaxControllerAbstract extends AjaxController
{
    protected $model;

    protected string $prevStructure;

    public function __construct()
    {
        Config::getInstance();
        $this->prevStructure = $this->getForumInPart();
    }

    /**
     * Добавление сообщения на форум
     */
    public function insertAction(): void
    {
        if (!isset($_POST['form'])) {
            exit;
        }

        parse_str($_POST['form'], $post);
        $post = $this->sanitizeInput($post);

        if (!isset($post['parent_id']) && !isset($post['main_parent_id']) && !isset($post['page_structure'])) {
            exit;
        }

        $post['email'] ??= '';

        $user = User\Model::getInstance();
        if (($post['email'] == '') && (isset($user->data['email']))) {
            $post['email'] = $user->data['email'];
        }

        $valid = $this->validation($post);
        if ($valid !== true) {
            echo $valid;
            exit();
        }

        $post['is_mail'] = $post['email'] != '';

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
    public function deleteAction(): void
    {
        if (!isset($_POST['ID']) || !isset($_POST['main_parent_id']) || !isset($_POST['parent_id'])) {
            exit;
        }

        $post['ID'] = htmlspecialchars($_POST['ID']);
        $post['main_parent_id'] = htmlspecialchars($_POST['main_parent_id']);
        $post['parent_id'] = htmlspecialchars($_POST['parent_id']);
        $this->model = new Model($this->prevStructure);
        $this->model->setPost($post);

        $result = $this->model->deletePost();
        if ($result) {
            exit('Ответ успешно удалён');
        }

        exit('Не удалось удалить ответ');

    }

    /**
     * Подтверждение на публикацию сообщения модератором
     */
    public function moderateAction(): void
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
    public function updateAction(): void
    {
        parse_str($_POST['form'], $post);
        $post = $this->sanitizeInput($post);

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
        }

        exit('Не удалось изменить ответ');

    }

    /**
     * Вывод формы для создания темы на форуме
     */
    public function getModalFormAction(): void
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
    public function getAnswerFormAction(): void
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

    protected function getForumInPart(): string
    {
        $config = Config::getInstance();
        $partTable = $config->db['prefix'] . 'ideal_structure_part';
        $_sql = sprintf("SELECT ID, prev_structure FROM %s WHERE structure = 'MiniForum_Post'", $partTable);
        $db = Db::getInstance();
        $forum = $db->select($_sql);

        if (!isset($forum[0]['prev_structure'])) {
            return '';
        }

        $partPrev = explode('-', $forum[0]['prev_structure']);
        return end($partPrev) . '-' . $forum[0]['ID'];
    }

    /**
     * @return bool|string
     * @param array<string, mixed> $post
     */
    protected function validation(array $post)
    {
        $msgValidation = '';
        $post['author'] ??= '';
        $post['content'] ??= '';
        $post['email'] ??= '';
        if (((string) $post['author'] === '') || ((string) $post['content'] === '')) {
            $msgValidation = 'Необходимо заполнить все поля формы';
        }

        if ((strlen($post['email']) !== 0) && (!Util::isEmail($post['email']))) {
            $msgValidation = 'Вы ввели неправильный почтовый адрес';
        }

        if (strlen($msgValidation) !== 0) {
            return 'MSG_Validation:' . $msgValidation;
        }

        return true;

    }

    /**
     * Очистка массива входных пользовательских данных
     *
     * @param array $post Массив входных данных
     */
    protected function sanitizeInput($post): array
    {
        return array_map('htmlspecialchars', $post);
    }
}
