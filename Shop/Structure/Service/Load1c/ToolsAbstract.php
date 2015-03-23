<?php
namespace Shop\Structure\Service\Load1c;

use Ideal;
use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Field\Url;
use Shop\Structure\Service\Load1c;
use Shop\Structure\Data;

class ToolsAbstract
{
    private $test;

    // Таблицы указываются без префекса
    protected $tableLink = ''; // таблица для связи категории и товара
    protected $tableGood = ''; // таблица товаров
    protected $tableCat  = ''; // таблица категорий

    protected $prevGood = ''; // prev_structure товаров
    protected $prevCat  = ''; // prev_structure категорий

    protected $fields = array();

    private $category = array();
    private $treeCat = array();

    public function __construct()
    {
        $config = Config::getInstance();
        $this->tableLink = $config->db['prefix'] . $this->tableLink;
        $this->tableGood = $config->db['prefix'] . $this->tableGood;
        $this->tableCat = $config->db['prefix'] . $this->tableCat;
        $this->test = "load";
    }


    function showProperties($importFile, $offersFile, $priceId)
    {
        $base = new Load1c\Model(DOCUMENT_ROOT . $importFile, DOCUMENT_ROOT . $offersFile, $priceId);

        // Отображение свойств товара, заданных в каталоге
        print '<pre>';
        print_r($base->getProperties());
        print '</pre>';
    }


    function showCategories($importFile, $offersFile, $priceId)
    {
        $base = new Load1c\Model(DOCUMENT_ROOT . $importFile, DOCUMENT_ROOT . $offersFile, $priceId);

        // Отображение структуры категорий товара, с указанием сколько товаров в каждой категории
        print '<pre>';
        $base->checkGoodsInGroups();
        print '</pre>';
    }


    function loadBase($importFile, $offersFile, $priceId, $loadImg = false)
    {
        $db = Db::getInstance();

        $base = new Load1c\Model(DOCUMENT_ROOT . $importFile, DOCUMENT_ROOT . $offersFile, $priceId);

        $fields = $this->fields;

        // УСТАНОВКА КАТЕГОРИЙ ТОВАРА ИЗ БД

        // Считываем категории из нашей БД
        $_sql = "SELECT ID, name, cid, lvl, id_1c, is_active, title FROM {$this->tableCat}
                    WHERE prev_structure='{$this->prevCat}'";
        $groups = $db->select($_sql);

        // Устанавливаем категории из БД
        $base->setOldGroups($groups);
        unset($groups);

        $txt = '';

        // ОБРАБОТКА КАТЕГОРИЙ ТОВАРА

        // Получаем изменённые категории
        $changedGroups = $base->getLoadGroups();

        echo '<h2>Категории</h2>';
        echo 'Добавлено: ' . count($changedGroups['add']) . '<br />'; //Пока не требуется
        echo 'Обновлено: ' . count($changedGroups['update']) . '<br />';
        echo 'Удалено: ' . count($changedGroups['delete']) . '<br />';

        $txt = $this->updateCategories($db, $this->tableCat, $txt, $changedGroups);
        unset($changedGroups);

        // ОБРАБОТКА ТОВАРА

        // Считываем товар из нашей БД;
        $_sql = "SELECT ID, name, id_1c, is_active FROM {$this->tableGood}
                    WHERE prev_structure='{$this->prevGood}'";
        $goods = $db->select($_sql);

        $changedGoods = $base->getGoods($fields, $goods);

        echo '<h2>Товары</h2>';
        echo 'Было: ' . count($goods) . '<br />';
        echo 'Добавлено: ' . count($changedGoods['add']) . '<br />';
        echo 'Обновлено: ' . count($changedGoods['update']) . '<br />';
        echo 'Удалено: ' . count($changedGoods['delete']) . '<br />';

        $txt = $this->updateGoods($db, $this->tableGood, $txt, $changedGoods, $loadImg);
        unset($changedGoods);

        // ПРИВЯЗКА ТОВАРА К КАТЕГОРИЯМ

        $this->updateGoodsToGroups($db, $base->getGoodsToGroups(), $base->status);

        echo str_replace("\n", '<br />', $txt);
    }


    function updateCategories(Db $db, $table, $txt, $changedGroups)
    {

        $modelType = new Data\Admin\Model('3-2');  //TODO нужно вынести
        $modelType->loadData();
        foreach ($changedGroups['update'] as $v) {
            if ($v['lvl'] == 1) $modelType->getIdData($v['name']);
            if ($v['Наименование'] != $v['name']) {
                $txt .= 'Переименована категория &laquo;' . $v['name'] . '&raquo; в &laquo;'
                    . $v['Наименование'] . "\n";
            }
            if (isset($v['old_cid_lvl'])) {
                $txt .= 'Категория &laquo;' . $v['name'] . '&raquo; перемещена. Было '
                    . $v['old_cid_lvl'] . ' стало cid=' . $v['cid'] . ', lvl=' . $v['lvl'] . "\n";
            }
            $update = array(
                'name' => $v['Наименование'],
                'cid' => $v['cid'],
                'lvl' => $v['lvl'],
                'is_active' => 1
            );
            $db->update($table, $v['ID'], $update);
        }

        $add = array(
            'date_create' => time(),
            'date_mod' => time(),
            'structure_path' => '1-3',
            'structure' => 'Shop_Category',
            'template' => 'Ideal_Page',
            'is_active' => 1
        );

        foreach ($changedGroups['add'] as $v) {
            $v['name'] = trim($v['name']);
            if ($v['lvl'] == 1) {
                $modelType->getIdData($v['Наименование']);
            }
            $v['id_1c'] = $v['Ид'];
            unset($v['Ид']);
            $v['name'] = $v['Наименование'];
            unset($v['Наименование']);

            $v['url'] = Url\Model::translitUrl($v['name']);

            $v = array_merge($v, $add);

            $db->insert($table, $v);
        }

        foreach ($changedGroups['delete'] as $v) {
            $par = array('is_active' => 1);
            $db->update($table, $v['ID'], $par);
            $txt .= 'Удалена категория: ' . $v['name'] . "\n";

        }

        return $txt;
    }


    function updateGoods(Db $db, $table, $txt, &$changedGoods, $loadImg = false)
    {
        $modelType = new Data\Admin\Model('3-1'); //TODO нужно вынести
        $modelBrand = new Data\Admin\Model('3-2'); //TODO нужно вынести
        $modelCategory = new Category\Admin\Model('3-3'); //TODO нужно вынести
        $modelCategory->loadCategory();
        $par = array();


        $modelType->loadData();
        $modelBrand->loadData();
        foreach ($changedGoods['update'] as $v) {
            $v['is_active'] = 1;
            if ($v['img'] != null) {
                if ($loadImg) {
                    $i = new Image($v['img'], 1000, 1000, 'big', false);
                }
                $image = basename($v['img']);
                $dir = basename(str_replace('/' . $image, '', $v['img']));
                $image = $dir . '/' . $image;
                $v['img'] = $image;

            }
            if ($v['ID'] == 0) {
                echo 'Невозможно обновить, т.к. нулевой ID.<br />';
                print_r($v);
                exit;
            }
            if (!isset($v['type'])) {
                $v['idType'] = $modelType->getIdData('Не указан');
            } else {
                $v['idType'] = $modelType->getIdData($v['type']);
            }
            $par['id_1c'] = $v['id_1c'];
            if ($v['category'] && $v['nameGroup']) {
                $idCat = $modelCategory->getIdCategory($v['category'] . $modelCategory->getGlue() . $v['nameGroup'], $par);
                $v['category_id'] = $idCat['ID'];
            }
            $v['idBrand'] = $modelBrand->getIdData($v['nameGroup']);
            unset($v['nameGroup']);
            unset($v['category']);
            $db->update($table, $v['ID'], $v);
        }

        $add = array(
            'date_create' => time(),
            'date_mod' => time(),
            'structure_path' => $this->prevGood,
            'is_active' => 1
        );

        foreach ($changedGoods['add'] as $v) {
            $v['name'] = trim($v['name']);
            $v['url'] = Url\Model::translitUrl(preg_replace('/ {2,}/',' ',$v['name']));
            $v = array_merge($v, $add);

            if (!isset($v['type'])) {
                $v['idType'] = $modelType->getIdData('Не указан');
            } else {
                $v['idType'] = $modelType->getIdData($v['type']);
            }
            if ($v['img'] != null) {
                if ($loadImg) {
                    $i = new Image($v['img'], 132, 132, 'big', false);
                }
                $image = basename($v['img']);
                $dir = basename(str_replace('/' . $image, '', $v['img']));
                $image = $dir . '/' . $image;
                $v['img'] = $image;

            }

            $par['id_1c'] = $v['id_1c'];
            $v['idBrand'] = $modelBrand->getIdData($v['nameGroup']);
            if ($v['category'] && $v['nameGroup']) {
                $idCat = $modelCategory->getIdCategory($v['category'] . $modelCategory->getGlue() . $v['nameGroup'], $par);
                $v['category_id'] = $idCat['ID'];
            }
            unset($v['nameGroup']);
            unset($v['category']);
            $db->insert($table, $v);
        }

        foreach ($changedGoods['delete'] as $v) {
            $par = array('is_active' => 0);
            if (!isset($v['ID']) OR ($v['ID'] == 0)) {
                // Если товара нет в БД, и у него нет цены — не добавляем его в базу
                continue;
            } else {
                $db->update($table, $v['ID'], $par);
            }
            $txt .= 'Удален товар: ' . $v['name'] . "\n";

        }

        return $txt;
    }


    function updateGoodsToGroups(Db $db, $goodsToGroups, $status)
    {
        $fields = array(
            'category_id' => array('sql' => 'int(11)'),
            'good_id' => array('sql' => 'int(11)')
        );

        if ($status == 'full') {
            $_sql = "DROP TABLE IF EXISTS {$this->tableLink}";
            $db->query($_sql);
            $db->create($this->tableLink, $fields);
            print 'Count goods to groups: ' . count($goodsToGroups) . '<br />';
            foreach ($goodsToGroups as $groupId => $goodIds) {
                foreach ($goodIds as $goodId) {
                    if ($groupId == '') continue;
                    $_sql = "SELECT ID FROM {$this->tableCat} AS t1 WHERE t1.id_1c='{$groupId}' LIMIT 1";
                    $id = $db->select($_sql);
                    $id = $id[0]['ID'];
                    $_sql = "SELECT ID FROM {$this->tableCat} AS t1 WHERE t1.id_1c='{$goodId}' LIMIT 1";
                    $id2 = $db->select($_sql);
                    $id2 = $id2[0]['ID'];
                    $row = array(
                        'category_id' => $id,
                        'good_id' => $id2
                    );
                    if ($row['category_id'] == '') continue;
                    $db->insert($this->tableCat, $row);
                }
            }
        } else {
            $goods = array();
            // Перестраиваем массив привязок с группа->товар на товар->группы
            foreach ($goodsToGroups as $groupId => $goodIds) {
                foreach ($goodIds as $goodId) {
                    $goods[$goodId] = $groupId;
                }
            }

            // Проходимся по массиву товаров: удаляем все привязки товара из БД, добавляем из XML
            foreach ($goods as $goodId => $groupsId) {
                $_sql = "DELETE FROM {$this->tableLink} WHERE good_id = '{$goodId}'";
                $db->query($_sql);
                foreach ($groupsId as $groupId) {
                    $row = array(
                        'category_id' => $groupId,
                        'good_id' => $goodId
                    );
                    $db->insert($this->tableLink, $row);
                }

            }

        }

    }

    /**
     * TODO создание категории если ее нет, с учётом аддонов
     * @param $id
     * @return mixed
     */
    private function createCategory($id)
    {
        $parentId = $this->treeCat[$id]['parent_id'];
        $cid = new \Ideal\Field\Cid\Model(6, 3);
        $lvl = 1;
        if($parentId !== false){
        } else{
            //$newCid = $cid->setBlock($nextParentCid, $newLvl, $newCid, true);

            $db = Db::getInstance();

            $insert = array(
                //'id_1c',
                'prev_structure' => $this->prevCat,
                'cid' => $cid,
                'lvl' => $lvl,
                'structure' => 'Shop_Category',
                'template' => 'Ideal_Page',
                //'name' => $child,
                //'url' => Url\Model::translitUrl($child),
                'num' => 1,
                'date_create' => time(),
                'date_mod' => time()
            );
        }
        return $id;
    }
}
