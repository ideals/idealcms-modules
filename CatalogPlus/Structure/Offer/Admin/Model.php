<?php

namespace CatalogPlus\Structure\Offer\Admin;

use Ideal\Structure\Roster\Admin\ModelAbstract;
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

    public function deleteByGood(): void
    {
        // Удаление прошло успешно, удаляем офферы, если они есть
        $db = Db::getInstance();

        $offers = $db->select(
            sprintf('SELECT * FROM %s WHERE prev_structure=:ps', $this->_table),
            ['ps' => $this->prevStructure],
        );

        foreach ($offers as $offer) {
            $offerModel = new Model('');
            $offerModel->setPageData($offer);
            $offerModel->delete();
        }
    }

    protected function getWhere($where)
    {
        if ($where != '') {
            $where = 'WHERE ' . $where;
        }

        return $where;
    }
}
