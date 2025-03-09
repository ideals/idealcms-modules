<?php
namespace Shop\Structure\Service\Load1CV3\Db\Offer;

use CatalogPlus\Structure\Offer\Site\Model;
use Shop\Structure\Service\Load1CV3\Db\AbstractDb;
use Ideal\Core\Db;
use Shop\Structure\Service\Load1CV3\Db\Good\DbGood;

class DbOffer extends AbstractDb
{
    protected string $nullableField = 'is_active';

    protected array $parse = [];

    /**
     *  Установка полей класса - полного имени таблиц с префиксом
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
        if (count($this->parse) !== 0) {
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
        $goods = $dbGood->getGoods('ID, id_1c, is_active', '');
        $goodPrevStructure = explode('-', $dbGood->prevGood);

        $db = Db::getInstance();
        $deactivated = [];

        foreach ($elements as $k => $element) {
            if (!isset($element['good_id'])) {
                // Возможно только при обновлении остатков, когда приходят остатки по складу, который мы не храним
                unset($elements[$k]);
                continue;
            }
            // todo временное отключение деактивации офферов/цен/остатков, так как 1С перешла на другую схему деактивации
//            if ($this->isOnlyUpdate && !isset($deactivated[$k])) {
//                $andOfferId = '';
//                if (strpos($k, '#') !== false) {
//                    $andOfferId = ' AND offer_id="' . $element['offer_id'] . '"';
//                }
//                // При загрузке только обновлений деактивируем все офферы этого товара, активные обновятся сами
//                // Когда загрузка полной базы - все офферы деактивируются на начальном этапе
//                $deactivated[$k] = true;
//                // todo продумать, как обнулять, когда нет записей об офферах/ценах/остатках
//                $db->update($this->table . $this->tablePostfix)
//                   ->set([$this->nullableField => 0])
//                   ->where('good_id="' . $element['good_id'] . '"' . $andOfferId)
//                   ->exec();
//            }
            if (isset($element['rest'])) {
                $elements[$k]['rest'] = (int) $element['rest'];
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
                if ($fieldName === 'ID') {
                    continue;
                }
                if (!isset($element[$fieldName])) {
                    $elements[$k][$fieldName] = $item['default'] ?? '';
                }
            }
        }

        parent::save($elements);
    }

    public function deactivateTable(): void
    {
        $db = Db::getInstance();
        $db->query("UPDATE {$this->table} SET is_active=0 WHERE is_1c_exchanged=0");
    }

    protected function copyOrigTable()
    {
        parent::copyOrigTable();

        $db = Db::getInstance();

        $testTable = $this->table . $this->tablePostfix;
        $sql = "UPDATE {$testTable} SET is_1c_exchanged=0";
        $db->query($sql);
    }
}
