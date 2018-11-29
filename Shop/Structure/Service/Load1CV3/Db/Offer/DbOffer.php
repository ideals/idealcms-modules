<?php
namespace Shop\Structure\Service\Load1CV3\Db\Offer;

use CatalogPlus\Structure\Offer\Site\Model;
use Shop\Structure\Service\Load1CV3\Db\AbstractDb;
use Ideal\Core\Db;
use Shop\Structure\Service\Load1CV3\Db\Good\DbGood;

class DbOffer extends AbstractDb
{
    protected $parse = array();
    /**
     *  Установка полей класса - полного имени таблиц с префикс
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = $this->prefix . 'catalogplus_structure_offer';
    }

    /**
     * Парсинг товаров из БД
     *
     * @return array ключ - id_1c, значение - все необходимые поля (в SQL)
     */
    public function parse()
    {
        if (count($this->parse) != 0) {
            return $this->parse;
        }

        $db = Db::getInstance();

        $sql = "SELECT * FROM " . $this->table . $this->tablePostfix;
        $result = $db->select($sql);

        foreach ($result as $item) {
            if ($item['offer_id'] == $item['good_id']) {
                $this->parse[$item['good_id']] = $item;
            } else {
                $this->parse[$item['good_id'] . '#' . $item['offer_id']] = $item;
            }
        }

        return $this->parse;
    }

    public function save($elements)
    {
        $offerModel = new Model('');

        $dbGood = new DbGood();
        $goods = $dbGood->getGoods('ID, id_1c, is_active', 'is_active=1');
        $goodPrevStructure = explode('-', $dbGood->prevGood);

        foreach ($elements as $k => $element) {
            if (isset($element['rest'])) {
                $elements[$k]['rest'] = (int) $element['rest'];
            }
            if (isset($element['count'])) {
                $elements[$k]['count'] = (int) $element['count'];
            } else {
                $elements[$k]['count'] = 0;
            }
            if (isset($element['inOrderCount'])) {
                $elements[$k]['inOrderCount'] = (int) $element['inOrderCount'];
            } else {
                $elements[$k]['inOrderCount'] = 0;
            }
            if (isset($element['price_old'])) {
                $elements[$k]['price_old'] = (int) $element['price_old'];
            }
            if (isset($element['good_id'], $goods[$element['good_id']])) {
                $itemStructure = $goodPrevStructure[1] . '-' . $goods[$element['good_id']]['ID'];
                $elements[$k]['prev_structure'] = $itemStructure;
            } else {
                $elements[$k]['prev_structure'] = '';
            }
            if (isset($element['ID'])) {
                continue;
            }
            foreach ($offerModel->fields as $fieldName => $item) {
                if ($fieldName == 'ID') {
                    continue;
                }

                if (!isset($element[$fieldName])) {
                    $elements[$k][$fieldName] = isset($item['default']) ? $item['default'] : '';
                }
            }
        }

        parent::save($elements);
    }

    protected function deactivateDataInTable()
    {
        parent::deactivateDataInTable();

        $db = Db::getInstance();

        $testTable = $this->table . $this->tablePostfix;
        $sql = "UPDATE {$testTable} SET price_old = 0, rest = 0";
        $db->query($sql);
    }
}
