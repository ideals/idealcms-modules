<?php
namespace CatalogPlus\Structure\Offer\Admin;

use Ideal\Core\Db;

class Model extends \Ideal\Structure\Roster\Admin\ModelAbstract
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

    public function deleteByGood()
    {
        // Удаление прошло успешно, удаляем офферы, если они есть
        $db = Db::getInstance();

        $offers = $db->select(
            "SELECT * FROM $this->_table WHERE prev_structure=:ps",
            ['ps' => $this->prevStructure]
        );

        foreach ($offers as $offer) {
            $offerModel = new \CatalogPlus\Structure\Offer\Admin\Model('');
            $offerModel->setPageData($offer);
            $offerModel->delete();
        }
    }
}