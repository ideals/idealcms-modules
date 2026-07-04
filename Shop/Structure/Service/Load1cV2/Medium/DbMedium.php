<?php

namespace Shop\Structure\Service\Load1cV2\Medium;

use Shop\Structure\Service\Load1cV2\AbstractDb;
use Ideal\Core\Db;
use Shop\Structure\Service\Load1cV2\Category\DbCategory;
use Shop\Structure\Service\Load1cV2\Good\DbGood;

class DbMedium extends AbstractDb
{
    /** @var string Таблица связи товаров и категорий */
    protected $table = 'catalogplus_medium_categorylist';

    /** @var array массив категорий с ID и id_1c */
    protected $categories;

    protected $goodToCat = [];

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
     */
    public function updateCategoryList($goodToGroup): void
    {
        $dbCategory = new DbCategory();
        $categories = $dbCategory->getCategories();

        $dbGood = new DbGood();
        $goods = $dbGood->getGoods('ID, id_1c');
        $result = [];
        $goodIds = [];
        foreach ($goodToGroup as $goodId => $groupIds) {
            if (!isset($goods[$goodId])) {
                // Непонятно, как такое возможно, товара нет, а связь есть?
                continue;
            }

            // Добавляем в список для удаления старых привязок к категориям для этого товара
            $goodIds[] = $goods[$goodId]['ID'];

            if (!is_array($groupIds) || ($groupIds === [])) {
                // Если товар не привязан ни к одной категории, то относим его к категории по умолчанию
                $result[] = [
                    'good_id' => $goods[$goodId]['ID'],
                    'category_id' => $categories['Load1c_default']['ID'],
                ];
                continue;
            }

            // Добавляем все привязки этого товара в массив для добавления в БД
            foreach ($groupIds as $groupId) {
                $result[] = [
                    'good_id' => $goods[$goodId]['ID'],
                    'category_id' => $categories[$groupId]['ID'],
                ];
            }
        }

        $db = Db::getInstance();

        // Удаление старых связей добавляемых товаров
        $goodIds = ['goodIds' => implode(',', $goodIds)];
        $db->delete($this->table . $this->tablePostfix)->where('good_id IN (:goodIds)', $goodIds)->exec();

        // Добавление связей по 25 штук в одном запросе
        while ($result !== []) {
            $part = array_splice($result, 0, 25);
            $db->insertMultiple($this->table . $this->tablePostfix, $part);
        }
    }

    /**
     * Подсчёт количества товаров в каждой группе
     *
     * @return array Список групп и количества товаров в каждой из них
     */
    public function countGoodsToGroup(): array
    {
        $db = Db::getInstance();

        $sql = "SELECT category_id as ID, count(DISTINCT good_id) as num from "
            . $this->table . $this->tablePostfix . " group by category_id";
        $res = $db->select($sql);
        $result = [];

        foreach ($res as $item) {
            $result[$item['ID']] = $item['num'];
        }

        return $result;
    }

    /**
     * Подготовка временной таблицы для выгрузки
     *
     * @param $onlyUpdate bool Файл Содержит Только Обновления
     */
    public function prepareTable($onlyUpdate): void
    {
        $this->onlyUpdate = $onlyUpdate;
        $this->dropTestTable();
        $this->createEmptyTestTable();
        if ($onlyUpdate) {
            $this->copyOrigTable();
        }
    }

    protected function getCategories(string $goodId)
    {
        $db = Db::getInstance();

        if (!array_key_exists($goodId, $this->goodToCat)) {
            $sql = sprintf('SELECT DISTINCT category_id FROM %s ', $this->table)
                . ('WHERE good_id = ' . $goodId);
            $categories = $db->select($sql);

            $this->goodToCat[$goodId] = [];
            foreach ($categories as $item) {
                $this->goodToCat[$goodId][] = $item['category_id'];
            }
        }

        return $this->goodToCat[$goodId];
    }

}
