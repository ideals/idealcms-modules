<?php

namespace Catalog\Structure\Offer\Admin;

use Ideal\Structure\Part\Admin\ModelAbstract;
use Ideal\Core\Db;

class Model extends ModelAbstract
{
    public function delete(): void
    {
        $db = Db::getInstance();
        $db->delete($this->_table)->where('ID=:id', ['id' => $this->pageData['ID']]);
        $db->exec();
        // TODO сделать проверку успешности удаления
        return 1;
    }

    protected function getWhere($where)
    {
        if ($where != '') {
            $where = 'WHERE ' . $where;
        }

        return $where;
    }

}
