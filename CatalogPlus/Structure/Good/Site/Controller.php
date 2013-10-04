<?php
namespace CatalogPlus\Structure\Good\Site;

class Controller extends \Ideal\Core\Site\Controller
{
    /**
     * @var $model Model
     */
    protected $model;

    public function indexAction()
    {
        parent::indexAction();
        $this->templateInit();

        $good = $this->model->getAboutGood();
        $good['mod'] = reset($good['offers']);
        $good['mod'] = $good['mod']['ID'];
        $this->view->good = $good;
    }

}
