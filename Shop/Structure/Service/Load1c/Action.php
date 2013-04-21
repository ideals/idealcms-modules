<?php
/*
План загрузки данных:
 * считываем все категории из файла распределяем их на обновление/добавление/удаление
 * считываем товары из БД
 * считываем товары из файла и распределяем их на обновление/добавление/удаление/пропуск
 * параллельно с разбором товара определяем кол-во товара в каждой категории,
   кроме тех, что на удаление и пропуск
 * из категорий в разделе «добавление» переносим в раздел «пропуск» те, у которых 0 товаров
 */

$priceId = 'd4d256de-2566-11dc-bc86-001617a7c060';


if (isset($_POST['load'])) {
    $import = $_POST['import'];
    $offers = $_POST['offers'];
    switch (intval($_POST['mode'])) {
        case '2':
            showCategories($import, $offers, $priceId);
            $mode2 = 'checked';
            break;
        case '3':
            showProperties($import, $offers, $priceId);
            $mode3 = 'checked';
            break;
        default:
            loadBase($import, $offers, $priceId);
            $mode1 = 'checked';
    }

} elseif (isset($_POST['save'])) {
    unset($_POST['save']);
    write_ini_file('ini.ini', $_POST);
}
$ini = parse_ini_file('ini.ini', true);
?>
    <script>
        $("#add").live("click", function () {
            $(this).parent().parent().after("<div class=\"control-group\">" +
                "<label class=\"control-label\" for=\"sizeimg\">Размер:</label>" +

                "<div class=\"controls\">" +
                "<input type=\"text\" name=\"sizeimg[]\"/> " +
                "<button type=\"button\" class=\"btn-mini btn-danger\" id=\"del\">DEL</button> " +
                "<button type=\"button\" class=\"btn-mini btn-success\" id=\"add\">ADD</button>" +
                "</div>" +
                "</div>");
        });
        $("#del").live("click", function () {
            $(this).parent().parent().html();
        });
    </script>

    <form method="POST" action="" class="form-horizontal" style="width: 60%; margin:30px auto;">
        <div class="control-group">
            <label class="control-label" for="tmp_dir">Каталог для сохранения временных данных:</label>

            <div class="controls">
                <input type="text" name="tmp_dir" value="<?php echo $ini['tmp_dir'] ?>"/>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="dirImage">Каталог для сохранения изображений:</label>

            <div class="controls">
                <input type="text" name="dirImage" value="<?php echo $ini['dirImage']; ?>"/>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="color">Цвет фона картинок:</label>

            <div class="controls">
                <input type="text" name="color" value="<?php echo $ini['color']; ?>"/>
            </div>
        </div>
        <?php foreach ($ini['sizeimg'] as $img) { ?>
            <div class="control-group">
                <label class="control-label" for="sizeimg">Размер:</label>

                <div class="controls">
                    <input type="text" name="sizeimg[]" value="<?php echo $img; ?>"/>
                    <button type="button" class="btn-mini btn-danger" id="del">DEL</button>
                    <button type="button" class="btn-mini btn-success" id="add">ADD</button>
                </div>
            </div>
        <?php } ?>
        <div class="control-group">
            <label class="control-label" for="import">Каталог:</label>

            <div class="controls">
                <input type="text" name="import" value="<?php echo $ini['import']; ?>"/>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="offers">Цены:</label>

            <div class="controls">
                <input type="text" name="offers" value="<?php echo $ini['offers']; ?>"/>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="manual">Ручная загрузка:</label>

            <div class="controls">
                <input type="checkbox" name="manual" value="1" <?php echo ($ini['manual'] == 1) ? 'checked' : ''; ?>/>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="offers">Режим:</label>

            <div class="controls">
                <label class="radio">
                    <input type="radio" name="mode" value="1" <?php echo ($ini['mode'] == 1) ? 'checked' : ''; ?>/>
                    Загрузка каталога
                </label>
                <label class="radio">
                    <input type="radio" name="mode" value="2" <?php echo ($ini['mode'] == 2) ? 'checked' : ''; ?>/>
                    Отображение структуры категорий товара
                </label>
                <label class="radio">
                    <input type="radio" name="mode" value="3" <?php echo ($ini['mode'] == 3) ? 'checked' : ''; ?>/>
                    Отображение свойств товара
                </label>
            </div>
        </div>
        <div style="text-align:center;">
            <input type="submit" class="btn btn-primary btn-large" name="load" value="Пуск"/>
            <input type="submit" class="btn btn-info btn-large" name="save" value="Сохранить"/>
        </div>
    </form>

<?php

function write_ini_file($path, $ini)
{
    $content = '';
    $sections = '';

    foreach ($ini as $key => $item) {
        if (is_array($item)) {
            $sections .= "\n[{$key}]\n";
            foreach ($item as $key2 => $item2) {
                if (is_numeric($item2) || is_bool($item2))
                    $sections .= "{$key2} = {$item2}\n";
                else
                    $sections .= "{$key2} = \"{$item2}\"\n";
            }
        } else {
            if (is_numeric($item) || is_bool($item))
                $content .= "{$key} = {$item}\n";
            else
                $content .= "{$key} = \"{$item}\"\n";
        }
    }

    $content .= $sections;

    if (!$handle = fopen($path, 'w')) {
        return false;
    }

    if (!fwrite($handle, $content)) {
        return false;
    }

    fclose($handle);
    return true;
}

function showProperties($importFile, $offersFile, $priceId)
{
    $base = new Ideal\Structure\Service\Load1c\Model(DOCUMENT_ROOT . $importFile, DOCUMENT_ROOT . $offersFile, $priceId);

    // Отображение свойств товара, заданных в каталоге
    print '<pre>';
    print_r($base->getProperties());
    print '</pre>';
}


function showCategories($importFile, $offersFile, $priceId)
{
    $base = new Ideal\Structure\Service\Load1c\Model(DOCUMENT_ROOT . $importFile, DOCUMENT_ROOT . $offersFile, $priceId);

    // Отображение структуры категорий товара, с указанием сколько товаров в каждой категории
    $base->checkGoodsInGroups();
}


function loadBase($importFile, $offersFile, $priceId)
{
    $db = Ideal\Core\Db::getInstance();

    $base = new Ideal\Structure\Service\Load1c\Model(DOCUMENT_ROOT . $importFile, DOCUMENT_ROOT . $offersFile, $priceId);

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

    $txt = updateGoods($db, $table, $txt, $changedGoods);
    unset($changedGoods);

    // ОБРАБОТКА КАТЕГОРИЙ ТОВАРА

    // Получаем изменённые категории
    $changedGroups = $base->getLoadGroups();

    echo '<h2>Категории</h2>';
    echo 'Добавлено: ' . count($changedGroups['add']) . '<br />';
    echo 'Обновлено: ' . count($changedGroups['update']) . '<br />';
    echo 'Удалено: ' . count($changedGroups['delete']) . '<br />';

    $table = 'i_structure_category';
    $txt = updateCategories($db, $table, $txt, $changedGroups);
    unset($changedGroups);

    // ПРИВЯЗКА ТОВАРА К КАТЕГОРИЯМ

    updateGoodsToGroups($db, $base->getGoodsToGroups(), $base->status);

    echo str_replace("\n", '<br />', $txt);
}


function updateCategories(Ideal\Core\Db $db, $table, $txt, $changedGroups)
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

        $v['url'] = Ideal\Field\Url\Model::translitUrl($v['cap']);

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


function updateGoods(Ideal\Core\Db $db, $table, $txt, &$changedGoods)
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
        $v['url'] = Ideal\Field\Url\Model::translitUrl($v['name']);
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


function updateGoodsToGroups(Ideal\Core\Db $db, $goodsToGroups, $status)
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