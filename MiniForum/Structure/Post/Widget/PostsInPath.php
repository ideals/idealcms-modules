<?php
namespace MiniForum\Structure\Post\Widget;

use Ideal\Core\Util;
use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Field;
use MiniForum\Structure\Post\Site;

class PostsInPath extends \Ideal\Core\Widget
{
    public  $path;
    public $prevStructure;

    public function __construct() {
        $config = Config::getInstance();
        $forum = $config->getStructureByName('MiniForum_Post');
        $this->prevStructure = $forum['params']['prev_structure'];
    }

    public function getData() {
        $model = new Site\Model($this->prevStructure);
        $model->setWhere(" AND page_structure = '{$this->pageStructure}' ");
        $posts = $model->getList(0);

        return $posts;
    }

    public function setPath($pageStructure) {
        $this->pageStructure = $pageStructure;
    }
}
