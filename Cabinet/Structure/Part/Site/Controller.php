<?php
namespace Cabinet\Structure\Part\Site;

class Controller extends \Ideal\Structure\Part\Site\ControllerAbstract
{

    /**
     * @var $model Model
     */
    public $model;

    public function indexAction()
    {
        $this->templateInit();

        if (session_id() == '') {
            session_start();
        }
        if (isset($_SESSION['login']['input'])) {
            $this->view->loginUser = $_SESSION['login']['user'];
            if ($_SESSION['login']['input'] === 2) {
                $this->view->header = 'Вашу учетную запись еще не активировали';
                return;
            }

            $pageData = $this->model->getPageData();

            $this->view->header = $this->model->getHeader();

            foreach ($pageData as $k => $v) {
                $this->view->$k = $v;
            }

            $this->view->parts = $this->model->getList(1, 999);
        }

    }
}
