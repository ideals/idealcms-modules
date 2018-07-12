<?php
namespace Shop\Structure\Service\Load1cV2\Medium;

use Shop\Structure\Service\Load1cV2\AbstractDb;
use Ideal\Field\Url;
use Shop\Structure\Service\Load1cV2\Category;
use Ideal\Core\Db;
use Shop\Structure\Service\Load1cV2\Category\DbCategory;
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
        $dbCategory = new DbCategory();
        $categories = $dbCategory->getCategories();

        $dbGood = new DbGood();
        $goods = $dbGood->getGoods('ID, id_1c');

        $result = $goodIds = array();
        foreach ($goodToGroup as $goodId => $groupIds) {
            if (!isset($goods[$goodId])) {
                // Непонятно, как такое возможно, товара нет, а связь есть?
                continue;
            }

            // Добавляем в список для удаления старых привязок к категориям для этого товара
            $goodIds[] = $goods[$goodId]['ID'];

            if (!is_array($groupIds) || (count($groupIds) == 0)) {
                // Если товар не привязан ни к одной категории, то относим его к категории по умолчанию
                $result[] = array(
                    'good_id' => $goods[$goodId]['ID'],
                    'category_id' => $categories['Load1c_default']['ID']
                );
                continue;
            }

            // Добавляем все привязки этого товара в массив для добавления в БД
            foreach ($groupIds as $groupId) {
                $result[] = array(
                    'good_id' => $goods[$goodId]['ID'],
                    'category_id' => $categories[$groupId]['ID']
                );
            }
        }

        $db = Db::getInstance();

        // Удаление старых связей добавляемых товаров
        $goodIds = array('goodIds' => implode(',', $goodIds));
        $db->delete($this->table . $this->tablePostfix)->where('good_id IN (:goodIds)', $goodIds)->exec();

        // Добавление связей по 25 штук в одном запросе
        while (count($result) > 0) {
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

        $sql = "SELECT category_id as ID, count(DISTINCT good_id) as num from "
            . $this->table . $this->tablePostfix . " group by category_id";
        $res = $db->select($sql);
        $result = array();

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
    public function prepareTable($onlyUpdate)
    {
        $this->onlyUpdate = $onlyUpdate;
        $this->dropTestTable();
        $this->createEmptyTestTable();
        if ($onlyUpdate) {
            $this->copyOrigTable();
        }
    }

}
