<?php
namespace Shop\Structure\Service\Load1c;

use Ideal;
use Ideal\Core\Db;
use Ideal\Field\Url;
use Shop\Structure\Service\Load1c;

class Tools
{
    private $test;

    public function __construct()
    {
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

        $fields = array(
            'Ид' => 'id_1c',
            'Наименование' => 'name',
            'БазоваяЕдиница' => 'measure',
            'Картинка' => 'img',
            'ЗначенияСвойств' => array(
                'Высота' => 'height',
                'Диаметр' => 'diameter',
                'Основное свойство' => 'general',
                'Размер ячейки' => 'cell',
                'Ширина' => 'width',
                'Марка стали' => 'steel',
                'Длина' => 'length',
                'Цвет' => 'color',
                'Количесто ребер жесткости' => 'rib',
            ),
            'ЗначенияРеквизитов' => array(
                'Полное наименование' => 'full_name',
                'Вес' => 'weight'
            ),
            /*
            'ХарактеристикиТовара' => array(
                'Размер' => 'size'
            ),
            */
            'Количество' => 'stock',
            'ЦенаЗаЕдиницу' => 'price',
            'Валюта' => 'currency',
            'Единица' => 'item',
            'Коэффициент' => 'coefficient'
        );

        // УСТАНОВКА КАТЕГОРИЙ ТОВАРА ИЗ БД

        // Считываем категории из нашей БД
        $table = 'i_shop_structure_category';
        $groups = $db->queryArray('SELECT ID, name, cid, lvl, id_1c, is_active, title FROM ' . $table . ' WHERE structure_path="1-96"');

        // Устанавливаем категории из БД
        $base->setOldGroups($groups);
        unset($groups);

        $txt = '';

        // ОБРАБОТКА ТОВАРА

        // Считываем товар из нашей БД
        $table = 'i_shop_structure_good';
        $goods = $db->queryArray('SELECT ID, name, id_1c, is_active FROM ' . $table . ' WHERE structure_path="4"');

        $changedGoods = $base->getGoods($fields, $goods);

        echo '<h2>Товары</h2>';
        echo 'Было: ' . count($goods) . '<br />';
        echo 'Добавлено: ' . count($changedGoods['add']) . '<br />';
        echo 'Обновлено: ' . count($changedGoods['update']) . '<br />';
        echo 'Удалено: ' . count($changedGoods['delete']) . '<br />';

        $txt = $this->updateGoods($db, $table, $txt, $changedGoods, $loadImg);
        unset($changedGoods);

        // ОБРАБОТКА КАТЕГОРИЙ ТОВАРА

        // Получаем изменённые категории
        $changedGroups = $base->getLoadGroups();

        echo '<h2>Категории</h2>';
        echo 'Добавлено: ' . count($changedGroups['add']) . '<br />'; //Пока не требуется
        echo 'Обновлено: ' . count($changedGroups['update']) . '<br />';
        echo 'Удалено: ' . count($changedGroups['delete']) . '<br />';

        $table = 'i_shop_structure_category';
        $txt = $this->updateCategories($db, $table, $txt, $changedGroups);
        unset($changedGroups);

        // ПРИВЯЗКА ТОВАРА К КАТЕГОРИЯМ

        $this->updateGoodsToGroups($db, $base->getGoodsToGroups(), $base->status);

        echo str_replace("\n", '<br />', $txt);
    }


    function updateCategories(Db $db, $table, $txt, $changedGroups)
    {
        foreach ($changedGroups['update'] as $v) {
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
            'structure_path' => '1-96',
            'structure' => 'Shop_Category',
            'template' => 'Ideal_Page',
            'is_active' => 1
        );

        foreach ($changedGroups['add'] as $v) {
            $v['id_1c'] = $v['Ид'];
            unset($v['Ид']);
            $v['name'] = $v['Наименование'];
            unset($v['Наименование']);

            $v['url'] = Url\Model::translitUrl($v['name']);

            $v = array_merge($v, $add);

            $db->insert($table, $v);
        }

        foreach ($changedGroups['delete'] as $v) {
            $par = array('is_active' => 0);
            $db->update($table, $v['ID'], $par);
            $txt .= 'Удалена категория: ' . $v['name'] . "\n";

        }

        return $txt;
    }


    function updateGoods(Db $db, $table, $txt, &$changedGoods, $loadImg = false)
    {
        foreach ($changedGoods['update'] as $v) {
            $v['is_active'] = 1;
            if ($loadImg) {
                if ($v['img'] != null) {
                    $img = $v['img'];
                    /*$i = new Image($img, 50, 50, 'small');
                    $v['img'] = $i->getName();*/
                    $i2 = new Image($img, 1000, 1000, 'big', false);
                    $v['img2'] = $i2->getName();
                } else {
                    $v['img'] = null;
                    $v['img2'] = null;
                }
            }
            if ($v['ID'] == 0) {
                echo 'Невозможно обновить, т.к. нулевой ID.<br />';
                print_r($v);
                exit;
            }
            $db->update($table, $v['ID'], $v);
        }

        $add = array(
            'date_create' => time(),
            'date_mod' => time(),
            'structure_path' => '4',
            'is_active' => 1
        );

        foreach ($changedGoods['add'] as $v) {
            $v['url'] = Url\Model::translitUrl($v['name']);
            $v = array_merge($v, $add);
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
        $table = 'i_good_category';
        $fields = array(
            'category_id' => array('sql' => 'int(11)'),
            'good_id' => array('sql' => 'int(11)')
        );

        if ($status == 'full') {
            $_sql = "DROP TABLE IF EXISTS {$table}";
            $db->query($_sql);
            $db->create($table, $fields);
            print 'Count goods to groups: ' . count($goodsToGroups) . '<br />';
            foreach ($goodsToGroups as $groupId => $goodIds) {
                foreach ($goodIds as $goodId) {
                    if ($groupId == '') continue;
                    $_sql = "SELECT ID FROM i_shop_structure_category AS t1 WHERE t1.id_1c='{$groupId}' LIMIT 1";
                    $id = $db->queryArray($_sql);
                    $id = $id[0]['ID'];
                    $_sql = "SELECT ID FROM i_shop_structure_good AS t1 WHERE t1.id_1c='{$goodId}' LIMIT 1";
                    $id2 = $db->queryArray($_sql);
                    $id2 = $id2[0]['ID'];
                    $row = array(
                        'category_id' => $id,
                        'good_id' => $id2
                    );
                    if ($row['category_id'] == '') continue;
                    $db->insert($table, $row);
                }
            }
        } else {
            $goods = array();
            // РџРµСЂРµСЃС‚СЂР°РёРІР°РµРј РјР°СЃСЃРёРІ РїСЂРёРІСЏР·РѕРє СЃ РіСЂСѓРїРїР°->С‚РѕРІР°СЂ РЅР° С‚РѕРІР°СЂ->РіСЂСѓРїРїС‹
            foreach ($goodsToGroups as $groupId => $goodIds) {
                foreach ($goodIds as $goodId) {
                    $goods[$goodId] = $groupId;
                }
            }

            // РџСЂРѕС…РѕРґРёРјСЃСЏ РїРѕ РјР°СЃСЃРёРІСѓ С‚РѕРІР°СЂРѕРІ: СѓРґР°Р»СЏРµРј РІСЃРµ РїСЂРёРІСЏР·РєРё С‚РѕРІР°СЂР° РёР· Р‘Р”, РґРѕР±Р°РІР»СЏРµРј РёР· XML
            foreach ($goods as $goodId => $groupsId) {
                $_sql = "DELETE FROM {$table} WHERE good_id = '{$goodId}'";
                $db->query($_sql);
                foreach ($groupsId as $groupId) {
                    $row = array(
                        'category_id' => $groupId,
                        'good_id' => $goodId
                    );
                    $db->insert($table, $row);
                }

            }

        }

    }
}
