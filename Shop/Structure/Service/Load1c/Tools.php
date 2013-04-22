<?php
namespace Shop\Structure\Service\Load1c;

class Tools
{
    private $test;
    public function __construct(){
        $this->test = "load";
    }
    function showProperties($importFile, $offersFile, $priceId)
    {
        $base = new \Shop\Structure\Service\Load1c\Model(DOCUMENT_ROOT . $importFile, DOCUMENT_ROOT . $offersFile, $priceId);

        // Отображение свойств товара, заданных в каталоге
        print '<pre>';
        print_r($base->getProperties());
        print '</pre>';
    }


    function showCategories($importFile, $offersFile, $priceId)
    {
        $base = new \Shop\Structure\Service\Load1c\Model(DOCUMENT_ROOT . $importFile, DOCUMENT_ROOT . $offersFile, $priceId);

        // Отображение структуры категорий товара, с указанием сколько товаров в каждой категории
        $base->checkGoodsInGroups();
    }


    function loadBase($importFile, $offersFile, $priceId)
    {
        $db = \Ideal\Core\Db::getInstance();

        $base = new \Shop\Structure\Service\Load1c\Model(DOCUMENT_ROOT . $importFile, DOCUMENT_ROOT . $offersFile, $priceId);

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
        $table = 'i_structure_category';
        $groups = $db->queryArray('SELECT ID, cap, cid, lvl, id_1c, is_active FROM ' . $table . ' WHERE structure_path="1-2"');

        // Устанавливаем категории из БД
        $base->setOldGroups($groups);
        unset($groups);

        $txt = '';

        // ОБРАБОТКА ТОВАРА

        // Считываем товар из нашей БД
        $table = 'i_structure_good';
        $goods = $db->queryArray('SELECT ID, name, id_1c, is_active FROM ' . $table . ' WHERE structure_path="4"');

        $changedGoods = $base->getGoods($fields, $goods);

        echo '<h2>Товары</h2>';
        echo 'Было: ' . count($goods) . '<br />';
        echo 'Добавлено: ' . count($changedGoods['add']) . '<br />';
        echo 'Обновлено: ' . count($changedGoods['update']) . '<br />';
        echo 'Удалено: ' . count($changedGoods['delete']) . '<br />';

        $txt = $this->updateGoods($db, $table, $txt, $changedGoods);
        unset($changedGoods);

        // ОБРАБОТКА КАТЕГОРИЙ ТОВАРА

        // Получаем изменённые категории
        $changedGroups = $base->getLoadGroups();

        echo '<h2>Категории</h2>';
        echo 'Добавлено: ' . count($changedGroups['add']) . '<br />';
        echo 'Обновлено: ' . count($changedGroups['update']) . '<br />';
        echo 'Удалено: ' . count($changedGroups['delete']) . '<br />';

        $table = 'i_structure_category';
        $txt = $this->updateCategories($db, $table, $txt, $changedGroups);
        unset($changedGroups);

        // ПРИВЯЗКА ТОВАРА К КАТЕГОРИЯМ

        $this->updateGoodsToGroups($db, $base->getGoodsToGroups(), $base->status);

        echo str_replace("\n", '<br />', $txt);
    }


    function updateCategories(\Ideal\Core\Db $db, $table, $txt, $changedGroups)
    {
        foreach ($changedGroups['update'] as $v) {
            if ($v['Наименование'] != $v['cap']) {
                $txt .= 'Переименована категория &laquo;' . $v['cap'] . '&raquo; в &laquo;'
                    . $v['Наименование'] . "\n";
            }
            if (isset($v['old_cid_lvl'])) {
                $txt .= 'Категория &laquo;' . $v['cap'] . '&raquo; перемещена. Было '
                    . $v['old_cid_lvl'] . ' стало cid=' . $v['cid'] . ', lvl=' . $v['lvl'] . "\n";
            }
            $update = array(
                'cap' => $v['Наименование'],
                'cid' => $v['cid'],
                'lvl' => $v['lvl'],
                'is_active' => 1
            );
            $db->update($table, $v['ID'], $update);
        }


        $add = array(
            'date_create' => time(),
            'date_mod' => time(),
            'structure_path' => '1-2',
            'structure' => 'Category',
            'template' => 'Page',
            'is_active' => 1
        );

        foreach ($changedGroups['add'] as $v) {
            $v['id_1c'] = $v['Ид'];
            unset($v['Ид']);
            $v['cap'] = $v['Наименование'];
            unset($v['Наименование']);

            $v['url'] = \Ideal\Field\Url\Model::translitUrl($v['cap']);

            $v = array_merge($v, $add);

            $db->insert($table, $v);
        }

        foreach ($changedGroups['delete'] as $v) {
            $par = array('is_active' => 0);
            $db->update($table, $v['ID'], $par);
            $txt .= 'Удалена категория: ' . $v['cap'] . "\n";

        }

        return $txt;
    }


    function updateGoods(\Ideal\Core\Db $db, $table, $txt, &$changedGoods)
    {
        foreach ($changedGoods['update'] as $v) {
            $v['is_active'] = 1;
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
            $v['url'] = \Ideal\Field\Url\Model::translitUrl($v['name']);
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


    function updateGoodsToGroups(\Ideal\Core\Db $db, $goodsToGroups, $status)
    {
        $table = 'i_good_category';
        $fields = array(
            'category_id' => array('sql' => 'char(37)'),
            'good_id' => array('sql' => 'char(37)')
        );

        if ($status == 'full') {
            $_sql = "DROP TABLE IF EXISTS {$table}";
            $db->query($_sql);
            $db->create($table, $fields);
            print 'Count goods to groups: ' . count($goodsToGroups) . '<br />';
            foreach ($goodsToGroups as $groupId => $goodIds) {
                foreach ($goodIds as $goodId) {
                    $row = array(
                        'category_id' => $groupId,
                        'good_id' => $goodId
                    );
                    $db->insert($table, $row);
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