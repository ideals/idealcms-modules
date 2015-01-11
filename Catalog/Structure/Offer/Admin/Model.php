<?php
namespace Catalog\Structure\Offer\Admin;

use Ideal\Core\Db;

class Model extends \Ideal\Structure\Part\Admin\ModelAbstract
{
    protected function getWhere($where)
    {
        if ($where != '') {
            $where = 'WHERE ' . $where;
        }
        return $where;
    }


    public function delete()
    {
        $db = Db::getInstance();
        $db->delete($this->_table)->where('ID=:id', array('id' => $this->pageData['ID']));
        $db->exec();
        // TODO сделать проверку успешности удаления
        return 1;
    }

}