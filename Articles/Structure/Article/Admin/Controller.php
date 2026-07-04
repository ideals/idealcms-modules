<?php

namespace Articles\Structure\Article\Admin;

use Ideal\Structure\Roster\Admin\ControllerAbstract;
use Ideal\Core\Request;
use Ideal\Core\Db;
use Ideal\Core\Config;

class Controller extends ControllerAbstract
{
    public function deleteAction(): void
    {
        $request = new Request();
        $config = Config::getInstance();
        $db = Db::getInstance();

        $result = [];
        $result['ID'] = intval($request->id);

        $catTable = $config->db['prefix'] . 'articles_medium_taglist';
        $db->query(sprintf('DELETE FROM %s WHERE article_id=%d', $catTable, $result['ID']));

        parent::deleteAction();
    }
}
