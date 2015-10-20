<?php
namespace Shop\Structure\Service\Load1cV2\Category;

use Shop\Structure\Service\Load1cV2\AbstractDb;
use Ideal\Field\Url;
use Ideal\Core\Config;
use Ideal\Field\Cid;
use Ideal\Core\Db;
use Shop\Structure\Service\Load1cV2\Medium\DbMedium;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:31
 */

class DbCategory extends AbstractDb
{
    /** @var string Структура для получения prev_structure */
    protected $structurePart = 'ideal_structure_part';

    /** @var string Предыдущая категория для prev_structure */
    protected $prevCat;

    /** @var string Все неприсвоенные категориям товары будут лежать тут */
    public $defaultCategory = '';

    /**
     *  Установка полей класса - полного имени таблиц с префиксами и получения prev_structure
     */
    public function __construct()
    {
        parent::__construct();
        $db = Db::getInstance();
        $this->table = $this->prefix . 'catalogplus_structure_category';
        $this->structurePart = $this->prefix . $this->structurePart;
        $res = $db->select(
            'SELECT ID FROM ' . $this->structurePart . ' WHERE structure = "CatalogPlus_Category" LIMIT 1'
        );
        $this->prevCat = '1-' . $res[0]['ID'];
    }


    /**
     * Если необходимо - создание категории товаров из выгрузки, у которых не была указана категория
     */
    public function createDefaultCategory()
    {
        $db = Db::getInstance();

        $res = $db->select('SELECT ID FROM ' . $this->table . $this->tablePostfix . ' WHERE name="Load1c_default"');
        if (count($res) == 0) {
            $config = Config::getInstance();
            $part = $config->getStructureByName('Ideal_Part');

            $cid = $db->select('SELECT max(cid) as cid FROM ' . $this->table . $this->tablePostfix);
            $cidModel = new Cid\Model($part['params']['levels'], $part['params']['digits']);
            $cid = $cidModel->getBlock($cid[0]['cid'], 1, '+1');
            $values = array(
                'lvl' => '1',
                'id_1c' => 'Load1c_default',
                'prev_structure' => $this->prevCat,
                'cid' => $cidModel->reconstruct($cid),
                'structure' => 'CatalogPlus_Category',
                'name' => 'Load1c_default',
                'url' => 'Load1c_default',
                'date_create' => time(),
                'date_mod' => time(),
                'is_active' => '0'
            );
            $db->insert($this->table . $this->tablePostfix, $values);
        }
    }

    /**
     * Получение списка категорий для распределения товаров из выгрузки
     *
     * @return array массив категорий ключ - id_1c, значение - ID категории в базе
     */
    public function getCategories()
    {
        $db = Db::getInstance();

        $categories = array();
        $res = $db->select('SELECT ID, id_1c, cid, lvl FROM ' . $this->table . $this->tablePostfix . ' ORDER BY cid');
        foreach ($res as $category) {
            $categories[$category['id_1c']] = $category;
        }

        return $categories;
    }

    /**
     * Получение массива категорий из базы данных
     *
     * @return array ключ - ид_1с, значение - массив полей в таблице о категории.
     */
    public function parse()
    {
        $db = Db::getInstance();

        // Сбрасываем счетчик товаров для групп
        $values = array(
            'num' => 0,
            'count_sale' => 0,
            'is_not_menu' => 0,
        );
        $db->update($this->table . $this->tablePostfix)
            ->set($values)
            ->exec();

        // Считываем категории из нашей БД
        $sql = "SELECT ID, name, cid, lvl, id_1c, is_active FROM `" .
            $this->table . $this->tablePostfix . "` ORDER BY cid";

        $tmp = $db->select($sql);

        $result = array();
        // Если категория не 1с ключ в массиве - порядковый номер, иначе - id_1c
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
     * Получение id_1c предка cid
     *
     * @param $parentCid string полный сид
     * @return null|string id_1c
     */
    public function getParentByCid($parentCid)
    {
        $db = Db::getInstance();

        $sql = "SELECT id_1c FROM " . $this->table . $this->tablePostfix . " WHERE cid = '{$parentCid}' LIMIT 1";
        $id = $db->select($sql);

        if (!isset($id[0]['id_1c'])) {
            return null;
        }

        return $id[0]['id_1c'];
    }

    public function recursiveRestruct(array &$categories, array $goodsCount, $cid = null, $lvl = 0, $k = null)
    {
        $config = Config::getInstance();
        $part = $config->getStructureByName('Ideal_Part');
        $cidModel = new Cid\Model($part['params']['levels'], $part['params']['digits']);

        foreach ($categories as $key => $category) {
            if (!isset($category['cid'])) {
                continue;
            }
            if (!is_null($cid)) {
                if (strpos($category['cid'], $cid) !== 0) {
                    // пропускаем если не родительский сид
                    continue;
                } else {
                    if ($category['lvl'] == $lvl) {
                        // пропускаем если тотже сид и уровень - также категория
                        continue;
                    }
                }
            }

            $cidNum = $cid . $cidModel->getBlock($category['cid'], $category['lvl']);
            $tmp = $this->recursiveRestruct($categories, $goodsCount, $cidNum, $category['lvl'], $key);

            if (!isset($categories[$key]['num'])) {
                if (isset($goodsCount[$categories[$key]['ID']])) {
                    $tmp += $goodsCount[$categories[$key]['ID']];
                }
                $categories[$key] = array();
                $categories[$key]['ID'] = $category['ID'];
                $categories[$key]['num'] = $tmp;
                if (intval($tmp) > 0) {
                    $categories[$key]['is_active'] = 1;
                }
            }

            if (!isset($count)) {
                $count = $tmp;
            } else {
                $count += $tmp;
            }
        }

        if (!isset($count)) {
            $count = isset($goodsCount[$categories[$k]['ID']]) ? $goodsCount[$categories[$k]['ID']] : 0;
        }
        return $count;
    }

    /**
     * Рекурсивное обновление количества товара во всех группах (с учётом товара в подгруппах)
     *
     * @param array $goodsCount Количество товара привязанного к каждой группе
     */
    public function updateGoodsCount()
    {
        // Считываем список категорий
        $categories = $this->getCategories();

        // Определяем количество товаров в каждой категории
        $dbMedium = new DbMedium();
        $goodsCount = $dbMedium->countGoodsToGroup();

        // Рекурсивно расставляем количество товаров в категории,
        // суммируюя количество товаров в подкатегориях
        $this->recursiveRestruct($categories, $goodsCount);

        $this->save($categories);
    }

    /**
     * Подготовка параметров категории для добавления в БД
     *
     * @param array $element Добавляемая категория
     * @return array Модифицированная категория
     */
    protected function getForAdd($element)
    {
        $element['url'] = Url\Model::translitUrl($element['name']);
        $element['prev_structure'] = $this->prevCat;
        $element['structure'] = 'CatalogPlus_Category';

        return parent::getForAdd($element);
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
        $this->copyOrigTable();
    }
}
