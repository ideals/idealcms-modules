<?php
namespace Shop\Structure\Service\Load1CV3\Db\Good;

use Shop\Structure\Service\Load1CV3\Db\AbstractDb;
use Ideal\Core\Db;
use Shop\Structure\Service\Load1CV3\Db\Order\DbOrder;

class DbGood extends AbstractDb
{
    /** @var string Структура для получения prev_structure */
    protected $structurePart = 'ideal_structure_part';

    /** @var string Предыдущая категория для prev_structure */
    public $prevGood;
    public $prevOffer;

    /** @var string Структуры категорий */
    protected $structureCat = 'catalogplus_structure_category';

    /** @var string Структуры категорий */
    protected $structureMedium = 'catalogplus_medium_categorylist';

    /** @var string Структуры категорий */
    protected $structureMediumTag = 'ideal_medium_taglist';

    /** @var array массив категорий с ID и id_1c */
    protected $categories;

    /** @var string Структура офферов */
    protected $offers = 'catalogplus_structure_offer';

    /** @var array 1С ключи для точечной выборки товаров из базы. */
    protected $goodKeys;

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
        $this->structureMediumTag = $this->prefix . $this->structureMediumTag;
        $this->offers = $this->prefix . $this->offers;
        $res = $db->select(
            'SELECT ID FROM ' . $this->structurePart . ' WHERE structure = "CatalogPlus_Good" LIMIT 1'
        );
        $this->prevGood = '1-' . $res[0]['ID'];
    }

    /**
     * Парсинг товаров из БД
     *
     * @return array ключ - id_1c, значение - все необходимые поля (в SQL)
     */
    public function parse()
    {
        $db = Db::getInstance();

        $goodKeysWhere = '';
        if ($this->goodKeys) {
            $goodKeysWhere = '\'' . implode('\',\'', $this->goodKeys) . '\'';
            $goodKeysWhere = ' AND sg.id_1c IN (' . $goodKeysWhere . ')';
        }

        // Считываем товары из нашей БД
        $sql = 'SELECT sg.ID, sg.img, sg.imgs, sg.full_name, sg.name, sg.id_1c, sg.is_active,sg.url, sg.url_full,
            sg.articul, sg.content
            FROM ' . $this->table . $this->tablePostfix .
            ' as sg WHERE sg.prev_structure=\'' . $this->prevGood . '\'' . $goodKeysWhere;

        $tmp = $db->select($sql);

        $result = array();
        foreach ($tmp as $element) {
            if ($element['id_1c'] === 'not-1c') {
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
    public function save($goods)
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
     * @param string $select
     * @param string $where
     * @return array key - id_1c
     */
    public function getGoods($select = '*', $where = '')
    {
        $db = Db::getInstance();

        $sql = "SELECT {$select} FROM " . $this->table . $this->tablePostfix;
        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }
        $res = $db->select($sql);
        $result = array();

        foreach ($res as $item) {
            $result[$item['id_1c']] = $item;
        }

        return $result;
    }

    public function updateGood()
    {

        $db = Db::getInstance();

        // Проверяем наличие тестовых таблиц, потому что их может не быть если происходит только лишь обмен заказами
        $result = $db->query('SHOW TABLES LIKE \'' . $this->offers . $this->tablePostfix . '\'');
        $res = $result->fetch_all(MYSQLI_ASSOC);
        if (count($res) > 0) {
            $sql = 'SELECT ID as offer_id_1c, price, good_id as id_1c, currency, rest as stock, price_old FROM '
                . $this->offers . $this->tablePostfix . ' WHERE is_active = 1 ORDER BY good_id, price';

            $tmp = $db->select($sql);
            $result = array();
            foreach ($tmp as $item) {
                // Если товара ещё нет в массиве, то добавляем его
                if (!isset($result[$item['id_1c']])) {
                    $result[$item['id_1c']] = $item;
                } else {
                    // Складываем остатки всех офферов
                    $result[$item['id_1c']]['stock'] += $item['stock'];

                    if ((float)$item['price'] > 0 &&
                        ((float)$result[$item['id_1c']]['price'] == 0 ||
                            $item['price'] < $result[$item['id_1c']]['price']
                        )
                    ) {
                        // Если товар уже есть в массиве, но у рассматриваемого оффера цена ниже, то обновляекм цену у
                        // товара
                        $result[$item['id_1c']]['price'] = $item['price'];
                    }
                    if ((float)$item['price_old'] > 0 &&
                        ((float)$result[$item['id_1c']]['price_old'] == 0 ||
                            (float)$item['price_old'] > (float)$result[$item['id_1c']]['price_old']
                        )
                    ) {
                        // Если товар уже есть в массиве, но у рассматриваемого оффера старая цена выше,
                        // то обновляем старую цену у товара
                        $result[$item['id_1c']]['price_old'] = (int)$item['price_old'];
                    }
                }
            }
            $goods = $this->getGoods('ID, id_1c, price, offer_id_1c, stock, currency, price_old', 'is_active = 1');

            $updates = array();
            foreach ($result as $k => $item) {
                if (isset($goods[$k])) {
                    $diff = array_diff_assoc($item, $goods[$k]);
                    if (count($diff) > 0) {
                        // ID товара всегда в диффе
                        $updates[$k] = $diff;
                        $updates[$k]['ID'] = $goods[$k]['ID'];
                    }
                }
            }
            parent::save($updates);
        }
    }

    /**
     * @param array $goodKeys
     */
    public function setGoodKeys($goodKeys)
    {
        $this->goodKeys = $goodKeys;
    }

    /**
     * @inheritdoc
     */
    public function onAfterSetDbElement($dbData, $xmlData)
    {
        // Экранируем кавычки в адресах
        $urlFull = addcslashes($xmlData['url_full'], '\'');

        // Ищем по данным из выгрузки товар с таким же url в БД
        $where = '(url_full = \'' . $urlFull . '\'';

        // Если переданный в xml адрес соответствует маске, значит такой адрес в базе может быть представлен не только
        // в поле "url_full", но и просто в поле "url", поэтому ищем в обоих полях
        if (preg_match('/\/product\/(.*?)\//', $xmlData['url_full'], $matches)
            && substr_count($xmlData['url_full'], '/') === 3
            ) {
            $where .= ' OR url = \'' . $urlFull . '\'';
        }

        // Исключаем из выборки товар который был только что добавлен/обновлён
        if ($dbData && isset($dbData['ID'])) {
            $where .= ') AND ID != ' . $dbData['ID'];
        } elseif (isset($xmlData['ID'])) {
            $where .= ') AND ID != ' . $xmlData['ID'];
        }

        $goods = $this->getGoods('*', $where);

        // Если нашлись товары с такими же адресами, то производим слияние информации и удалении данных о схожих товарах
        if ($goods) {
            // Если нашлось больше одного товара, то сортируем их в порядке возрастания даты модификации
            // Тот что модифицировался недавно окажется в самом верху.
            if (count($goods) > 1) {
                uasort($goods, function ($curr, $next) {
                    return $next['date_mod'] - $curr['date_mod'];
                });
            }
            $good = array_shift($goods);

            // По очереди проходим все товары со схожими адресами и данные из них записываем в пустые поля самого
            // свежего товара
            foreach ($goods as $item) {
                $keys = array_filter($good);
                $keys = array_keys(array_diff_assoc($good, $keys));
                if (!$keys) {
                    break;
                }
                foreach ($keys as $key) {
                    if ($item[$key]) {
                        $good[$key] = $item[$key];
                    }
                }
            }

            // Возвращаем товар со сплющенными данными в общий массив похожих товаров для дальнейшего устранения
            // повторений
            $goods[$good['id_1c']] = $good;

            // Перезаписываем сплющенные данные по товару данными из xml
            $good = array_merge($good, $xmlData);
            $this->update($good);

            // Удаляем повторяющиеся товары
            $this->delGoods($goods);

            // Запускаем процесс замены идентификаторов товаров в заказе на новые
            $dbOrder = new DbOrder();
            $dbOrder->changeGoodInOrder($good, $goods);
        }
    }

    /**
     * Подготовка параметров товара для добавления в БД
     *
     * @param array $element Добавляемый товар
     * @return array Модифицированный товар
     */
    protected function getForAdd($element)
    {
        $element['prev_structure'] = $this->prevGood;
        $element['measure'] = '';
        $element['structure'] = 'CatalogPlus_Offer';

        return parent::getForAdd($element);
    }

    /**
     * Удаляет информацию о товарах по всей базе
     *
     * @param array $goods Массив с информацией по товарам
     */
    protected function delGoods($goods)
    {
        // Удяляются только те товары, которые имеют идентификатор
        // Собираем идентификаторы товаров для удаления
        $delIds = array();
        foreach ($goods as $good) {
            if (isset($good['ID'])) {
                $delIds[] = $good['ID'];
            }
        }

        if ($delIds) {
            $db = Db::getInstance();

            // Удаяем сами товары
            $whereId = implode(',', $delIds);
            $sql = "DELETE FROM {$this->table}{$this->tablePostfix} WHERE ID IN ({$whereId})";
            $db->query($sql);

            // Удаляем офферы
            list(, $goodStructureId) = explode('-', $this->prevGood);
            $wherePrevStructure = '\'' . $goodStructureId . '-' . implode('\',\'' . $goodStructureId . '-', $delIds);
            $wherePrevStructure .= '\'';
            $sql = "DELETE FROM {$this->offers}{$this->tablePostfix} WHERE prev_structure IN ({$wherePrevStructure})";
            $db->query($sql);

            // удаляем теги товара
            $sql = "DELETE FROM {$this->structureMediumTag}{$this->tablePostfix} WHERE structure_id={$goodStructureId}";
            $sql .= " AND part_id IN ({$whereId})";
            $db->query($sql);

            // Удаляем отнесение к разделам каталога
            $sql = "DELETE FROM {$this->structureMedium}{$this->tablePostfix} WHERE good_id IN ({$whereId})";
            $db->query($sql);
        }
    }
}
