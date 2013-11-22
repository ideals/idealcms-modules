<?php
namespace MiniForum\Structure\Post\Site;

use Ideal\Core\Util;
use Ideal\Core\Config;

class AjaxController extends \Ideal\Core\Site\AjaxController
{
    protected $model;
    protected $structurePath = '1-13';

    function insetAction() {
        $form = $_POST['form'];
        parse_str($form, $post);

        $valid = $this->validation($post);
        if ($valid !== true) {
            echo $valid;
            return;
        }

        $this->model = new Model($this->structurePath);
        $this->model->setPost($post);
        $result = $this->model->addNewPost();

        $post['ID'] = $result;
        $this->model->sendMessages($post);


        echo $result;

    }

    public function deleteAction()
    {
        $post['ID'] = $_POST['ID'];
        $post['main_parent_id'] = $_POST['main_parent_id'];
        $post['parent_id'] = $_POST['parent_id'];
        $this->model = new Model($this->structurePath);
        $this->model->setPost($post);
        $result = $this->model->deletePost();
        if ($result) {
            echo 'Ответ успешно удалён';
        } else {
            echo 'Не удалось удалить ответ';
        }
    }


    function updateAction() {
        $form = $_POST['form'];
        parse_str($form, $post);

        $valid = $this->validation($post);
        if ($valid !== true) {
            echo $valid;
            return;
        }

        $this->model = new Model($this->structurePath);
        $this->model->setPost($post);
        $result = $this->model->updatePost();
        if ($result) {
            echo 'Ответ успешно изменён';
        } else {
            echo 'Не удалось изменить ответ';
        }
    }

    function getModalFormAction() {
        parse_str($_GET['formValues'], $formValues);

        if ($formValues['ID'] !== '0') {
            $this->model = new Model($this->structurePath);
            $post = $this->model->getPost($formValues['ID']);
            $post = $post[0];
            $formValues['content'] = $post['content'];
            $formValues['authorPF'] = $post['author'];
            $formValues['emailPF'] = $post['email'];
        }
        //$formValues['content'] = str_replace('<br />', '\r\n', $formValues['content']);
        //$formValues['content'] = str_replace('<br>', '\r\n', $formValues['content']);

        $modalForm = stream_resolve_include_path('MiniForum/Structure/Post/Site/modalForm.php');
        include($modalForm);
        exit;
    }

    function getAnswerFormAction() {
        parse_str($_GET['formValues'], $formValues);
        if (($formValues['mainParentId'] != '0') && ($formValues['pageStructurePostId'] != '0')) {
            $goToPost = true;
        } else {
            $goToPost = false;
        }
        //parse_str($_GET['formValues'], $formValues);
        $answerForm = stream_resolve_include_path('MiniForum/Structure/Post/Site/answerForm.php');
        include($answerForm);
    }

    function validation($post) {
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