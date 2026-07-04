<?php

namespace Shop\Structure\Service\Load1cV2\Good;

use Shop\Structure\Service\Load1cV2\AbstractDb;
use Ideal\Core\Db;

class DbGood extends AbstractDb
{
    /** @var string Предыдущая категория для prev_structure */
    public $prevGood;

    public $prevOffer;

    /** @var string Структура для получения prev_structure */
    protected string $structurePart = 'ideal_structure_part';

    /** @var string Структуры категорий */
    protected string $structureCat = 'catalogplus_structure_category';

    /** @var string Структуры категорий */
    protected string $structureMedium = 'catalogplus_medium_categorylist';

    /** @var array массив категорий с ID и id_1c */
    protected $categories;

    /** @var string Структура офферов */
    protected string $offers = 'catalogplus_structure_offer';

    /**
     *  Установка полей класса - полного имени таблиц с префиксами и получения prev_structure
     */
    public function __construct()
    {
        parent::__construct();
        $db = Db::getInstance();
        $this->table = $this->prefix . 'catalogplus_structure_good';
        $this->structurePart = $this->prefix . $this->structurePart;
        $this->structureCat = $this->prefix . $this->structureCat;
        $this->structureMedium = $this->prefix . $this->structureMedium;
        $this->offers = $this->prefix . $this->offers;
        $res = $db->select(
            'SELECT ID FROM ' . $this->structurePart . ' WHERE structure = "CatalogPlus_Good" LIMIT 1',
        );
        $this->prevGood = '1-' . $res[0]['ID'];
    }

    /**
     * Парсинг товаров из БД
     *
     * @return array ключ - id_1c, значение - все необходимые поля (в SQL)
     */
    public function parse(): array
    {
        $db = Db::getInstance();

        // Считываем товары из нашей БД
        $sql = "SELECT sg.ID, sg.img, sg.imgs, sg.full_name, sg.name, sg.id_1c, sg.is_active,
            sg.url, sg.articul, sg.content
            FROM " . $this->table . $this->tablePostfix
            . sprintf(" as sg WHERE sg.prev_structure='%s'", $this->prevGood);

        $tmp = $db->select($sql);

        $result = [];
        foreach ($tmp as $element) {
            if ($element['id_1c'] == 'not-1c') {
                $result[] = $element;
            } else {
                $result[$element['id_1c']] = $element;
            }
        }

        return $result;
    }

    /**
     * Сохранение изменений и добавление новых товаров в БД
     *
     * @param array $goods массив товаров для сохранения
     */
    public function save($goods): void
    {
        foreach ($goods as $k => $good) {
            if (!isset($good['prev_structure'])) {
                $goods[$k]['prev_structure'] = $this->prevGood;
            }

            $goods[$k]['structure'] = 'CatalogPlus_Offer';
        }

        parent::save($goods);
    }

    /**
     * Получение информации о товарах
     *
     * @return array key - id_1c
     */
    public function getGoods(string $select = '*', $where = ''): array
    {
        $db = Db::getInstance();

        $sql = sprintf('SELECT %s FROM ', $select) . $this->table . $this->tablePostfix;
        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }

        $res = $db->select($sql);
        $result = [];

        foreach ($res as $item) {
            $result[$item['id_1c']] = $item;
        }

        return $result;
    }

    public function updateGood(): void
    {
        $db = Db::getInstance();

        $sql = 'SELECt ID as offer_id_1c, price, good_id as id_1c, currency, rest as stock FROM '
            . $this->offers . $this->tablePostfix . ' ORDER BY good_id, price';

        $result = [];
        $tmp = $db->select($sql);
        foreach ($tmp as $item) {
            // Если товара ещё нет в массиве, то добавляем его
            if (!isset($result[$item['id_1c']])) {
                $result[$item['id_1c']] = $item;
            } elseif (floatval($item['price']) > 0
                && (
                    floatval($result[$item['id_1c']]['price']) == 0
                    || $item['price'] < $result[$item['id_1c']]['price']
                )
            ) {
                // Если товар уже есть в массиве, но у рассматриваемого оффера цена ниже, то обновляекм цену у товара
                $result[$item['id_1c']]['price'] = $item['price'];
            }
        }

        $goods = $this->getGoods('ID, id_1c, price, offer_id_1c, stock, currency');

        $updates = [];
        foreach ($result as $k => $item) {
            if (isset($goods[$k])) {
                $diff = array_diff_assoc($item, $goods[$k]);
                if ($diff !== []) {
                    // ID товара всегда в диффе
                    $updates[$k] = $diff;
                    $updates[$k]['ID'] = $goods[$k]['ID'];
                }
            }
        }

        parent::save($updates);
    }

    /**
     * Подготовка параметров товара для добавления в БД
     *
     * @param array $element Добавляемый товар
     * @return array Модифицированный товар
     */
    protected function getForAdd(array $element): array
    {
        $element['prev_structure'] = $this->prevGood;
        $element['measure'] = '';
        $element['structure'] = 'CatalogPlus_Offer';

        return parent::getForAdd($element);
    }
}
