<?php
namespace Articles\Structure\Article\Admin;

use Ideal\Core\Request;
use Ideal\Core\Db;
use Ideal\Core\Config;

class Controller extends \Ideal\Structure\Roster\Admin\ControllerAbstract
{
    public function deleteAction()
    {
        $request = new Request();
        $config = Config::getInstance();
        $db = Db::getInstance();

        $result = array();
        $result['ID'] = intval($request->id);

        $catTable = $config->db['prefix'] . 'articles_medium_taglist';
        $db->query("DELETE FROM {$catTable} WHERE article_id={$result['ID']}");

        parent::deleteAction();
    }
}
