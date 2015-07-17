<?php
namespace Shop\Structure\Service\Load1c_v2\Offer;

use Shop\Structure\Service\Load1c_v2\AbstractDb;
use Ideal\Field\Url;
use Ideal\Core\Db;
use Shop\Structure\Service\Load1c_v2\Category;
use Shop\Structure\Service\Load1c_v2\Good\DbGood;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:31
 */

class DbOffer extends AbstractDb
{
    /** @var string Структуры категорий */
    protected $structureGood = 'catalogplus_structure_good';

    /**
     *  Установка полей класса - полного имени таблиц с префикс
     */
    public function __construct()
    {
        parent::__construct();
        $this->structureGood = $this->prefix . $this->structureGood;
        $this->table = $this->prefix . 'offer_good';
    }

    /**
     * Парсинг товаров из БД
     *
     * @return array ключ - id_1c, значение - все необходимые поля (в SQL)
     */
    public function parse()
    {
        $db = Db::getInstance();

        $sql = "SELECT * FROM `{$this->table}`";
        $result = $db->select($sql);
        $data = array();

        foreach ($result as $item) {
            if ($item['offer_id'] == $item['good_id']) {
                $data[$item['good_id']] = $item;
            } else {
                $data[$item['good_id'] . '#' . $item['offer_id']] = $item;
            }
        }

        return $data;
    }

    public function save($elements)
    {
        parent::save($elements);

        $this->updateGoods($elements);
    }

    // квадрат чилса используя 2 переменных и только + и -

    protected function updateGoods($elements)
    {
        $dbGoods = new DbGood();

        $updates = array();
        $goods = $dbGoods->getGoods();
        foreach ($elements as $key => $value) {
            $id1c = $value['good_id'];
            if (is_null($goods[$id1c]['price']) || $value['price'] < $goods[$id1c]['ID']) {
                $updates[$id1c]['price'] = $value['price'];
            }
            if ($value['currency'] != $goods[$id1c]['currency']) {
                $updates[$id1c]['currency'] = $value['currency'];
            }
            if ($value['coefficient'] != $goods[$id1c]['coefficient']) {
                $updates[$id1c]['coefficient'] = $value['coefficient'];
            }
            if (count($updates[$id1c]) > 0) {
                $updates[$id1c]['ID'] = $goods[$id1c]['ID'];
            }
        }

        $dbGoods->save($updates);
    }
}
