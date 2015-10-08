<?php
namespace Shop\Structure\Service\Load1cV2\Medium;

use Shop\Structure\Service\Load1cV2\AbstractDb;
use Ideal\Field\Url;
use Shop\Structure\Service\Load1cV2\Category;
use Ideal\Core\Db;
use Shop\Structure\Service\Load1cV2\Good\DbGood;

class DbMedium extends AbstractDb
{
    /** @var string Таблица связи товаров и категорий */
    protected $table = 'catalogplus_medium_categorylist';

    /** @var array массив категорий с ID и id_1c */
    protected $categories;

    protected $goodToCat = array();

    /**
     *  Установка полей класса - полного имени таблиц с префиксами и получения prev_structure
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = $this->prefix . $this->table;
    }

    /**
     * Обновление таблицы связи товаров с категориями
     *
     * @param $goodToGroup
     */
    public function updateCategoryList($goodToGroup)
    {
        $dbGood = new DbGood();
        $goods = $dbGood->getGoods('ID, id_1c');

        $result = array();
        foreach ($goodToGroup as $item) {
            if (!isset($goods[$item['good_id']])) {
                // Непонятно, как такое возможно, товара нет, а связь есть?
                continue;
            }

            $categories = $this->getCategories($goods[$item['good_id']]['ID']);

            if (!in_array($this->categories[$item['category_id']]['ID'], $categories)) {
                $result[] = array(
                    'good_id' => $goods[$item['good_id']]['ID'],
                    'category_id' => $this->categories[$item['category_id']]['ID']
                );
            }
        }

        // todo удаление старых связей добавляемых товаров

        // Добавление связей по 25 штук в одном запросе
        $db = Db::getInstance();
        while (count($result) > 24) {
            $part = array_splice($result, 0, 25);
            $db->insertMultiple($this->table . $this->tablePostfix, $part);
        }
    }

    protected function getCategories($goodId)
    {
        $db = Db::getInstance();

        if (!array_key_exists($goodId, $this->goodToCat)) {
            $sql = "SELECT DISTINCT category_id FROM {$this->table} ".
                "WHERE good_id = {$goodId}";
            $categories = $db->select($sql);

            $this->goodToCat[$goodId] = array();
            foreach ($categories as $item) {
                $this->goodToCat[$goodId][] = $item['category_id'];
            }
        }

        return $this->goodToCat[$goodId];
    }

    /**
     * Подсчёт количества товаров в каждой группе
     *
     * @return array Список групп и количества товаров в каждой из них
     */
    public function countGoodsToGroup()
    {
        $db = Db::getInstance();

        $sql = "SELECT category_id as ID, count(good_id) as num from "
            . $this->table . $this->tablePostfix . " group by category_id";
        $res = $db->select($sql);
        $result = array();

        foreach ($res as $item) {
            $result[$item['ID']] = $item['num'];
        }

        return $result;
    }


}
