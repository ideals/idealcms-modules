<?php
namespace Shop\Structure\Service\Load1CV3\Db\TagMedium;

use Shop\Structure\Service\Load1CV3\Db\AbstractDb;
use Ideal\Core\Db;
use Shop\Structure\Service\Load1CV3\Db\Tag\DbTag;
use Shop\Structure\Service\Load1CV3\Db\Good\DbGood;

class DbTagMedium extends AbstractDb
{
    /** @var string Таблица связи товаров и тегов */
    protected $table = 'ideal_medium_taglist';

    /** @var array массив тегов с ID */
    protected $tags;

    /**
     *  Установка полей класса - полного имени таблицы с префиксом
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = $this->prefix . $this->table;
    }

    /**
     * Обновление таблицы связи товаров с тегами
     *
     * @param $goodToTag
     */
    public function updateTagList($goodToTag)
    {
        $dbTag = new DbTag();
        $tags = $dbTag->getTags();

        $dbGood = new DbGood();
        $goods = $dbGood->getGoods('ID, id_1c', 'is_active = 1');

        $result = $goodIds = array();
        foreach ($goodToTag as $goodId => $tagsUrl) {
            if (!isset($goods[$goodId])) {
                // Непонятно, как такое возможно, товара нет, а связьс тегом есть?
                continue;
            }

            // Добавляем информацию о товаре в список для удаления старых привязок к тегам
            $goodIds[] = $goods[$goodId]['ID'];

            // Добавляем все привязки этого товара в массив для добавления в БД
            foreach ($tagsUrl as $urlTag) {
                $result[] = array(
                    'part_id' => $goods[$goodId]['ID'],
                    'tag_id' => $tags[$urlTag]['ID'],
                    'structure_id' => '11'
                );
            }
        }

        $db = Db::getInstance();

        // Удаление старых связей добавляемых товаров
        if (!empty($goodIds)) {
            $db->delete($this->table . $this->tablePostfix)
               ->where('part_id IN (' . implode(',', $goodIds) . ')')
               ->exec();
        }

        // Добавление связей по 25 штук в одном запросе
        while (count($result) > 0) {
            $part = array_splice($result, 0, 25);
            $db->insertMultiple($this->table . $this->tablePostfix, $part);
        }
    }

    /**
     * Подготовка временной таблицы для выгрузки
     */
    public function prepareTable()
    {
        $this->dropTestTable();
        $this->createEmptyTestTable();
        if ($this->isOnlyUpdate) {
            $this->copyOrigTable();
        }
    }

}
