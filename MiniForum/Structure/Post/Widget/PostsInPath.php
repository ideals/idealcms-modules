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
    public function getData() {
        $model = new Site\Model('1-13');
        $model->setWhere(" AND page_structure = '{$this->pageStructure}' ");
        $posts = $model->getList(0);

        return $posts;
    }

    public function setPath($pageStructure) {
        $this->pageStructure = $pageStructure;
    }
}
