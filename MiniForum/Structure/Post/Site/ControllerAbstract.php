<?php
namespace MiniForum\Structure\Post\Site;

use Ideal\Core\Config;
use Ideal\Core\Request;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    /** @var  $model Model */
    protected $model;
    protected $title; // Отвечает за титле на сайте

    public function indexAction()
    {
        parent::indexAction();
        $this->view->Authorized = isset($_SESSION['IsAuthorized']) ? $_SESSION['IsAuthorized'] : '';
        $request = new Request();
        $page = intval($request->page);
        $this->view->posts = $this->model->getList($page);

        if ($page !== 0) {
            $title = $this->title. ' - Страница ' . $page;
            $this->model->setTitle($title);
        }

        // Отображение листалки
        $this->view->pager = $this->model->getPager('page');
    }

    public function detailAction()
    {
        $this->templateInit('MiniForum/Structure/Post/Site/detail.twig');

        $this->view->prevStructure = 0;
        $pageData = $this->model->getPageData();

        // Задаём название в хлебных крошках
        $path = $this->model->getPath();
        $path[count($path) - 1]['name'] = '';
        $this->model->setPath($path);

        $text = $this->model->splitMessage($pageData['content'], 30, 0);
        $this->view->header = strip_tags($text[0]);
        $pageData['content'] = $text[1];
        $this->view->mainPost =  $pageData;

        $this->view->posts = $this->model->getChildPosts();
        $this->view->Authorized = isset($_SESSION['IsAuthorized']) ? $_SESSION['IsAuthorized'] : '';

        $config = Config::getInstance();

        if (isset($_SERVER['HTTP_REFERER']) && ($_SERVER['HTTP_REFERER'] !== '')) {
            $_SESSION['HTTP_REFERER'] = $_SERVER['HTTP_REFERER'];
        }
        $this->view->forumLink = '/forum' . $config->urlSuffix;

        if (isset($_GET['email']) && isset($_GET['hash'])) {
            $unsubject = $this->model->unsubjectLink($_GET['email'], $_GET['id'], $_GET['post'], $_GET['hash']);
            if ($unsubject) {
                $this->view->script = "alert('Вы успешно отписались от сообщений с данного раздела форума.')";
            }
        }

        //Устанавливаем мета теги description и keywords
        $this->model->setTitle();
        $this->model->setKeywords();
        $this->model->setDescription();
    }
} 