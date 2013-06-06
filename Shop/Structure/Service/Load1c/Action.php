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
$base = new \Shop\Structure\Service\Load1c\Tools();
$_POST['priceId'] = 'd4d256de-2566-11dc-bc86-001617a7c060';


if (isset($_POST['load'])) {
    $import = $_POST['import'];
    $offers = $_POST['offers'];
    $priceId = $_POST['priceId'];
    switch (intval($_POST['mode'])) {
        case '2':
            $base->showCategories($import, $offers, $priceId);
            $mode2 = 'checked';
            break;
        case '3':
            $base->showProperties($import, $offers, $priceId);
            $mode3 = 'checked';
            break;
        case '4':
            $base->showMappingCategories($import, $offers, $priceId);
            $mode4 = 'checked';
            break;
        case '5':
            $base->showSaveMapping('/tmp/1c/import.xml', '/tmp/1c/offers.xml', $priceId, $_POST['form']);
            $mode4 = 'checked';
            break;
        default:
            $base->loadBase($import, $offers, $priceId);
    }
} elseif (isset($_POST['save'])) {
    unset($_POST['save']);
    write_ini_file('ini.ini', $_POST);
}
$ini = parse_ini_file('ini.ini', true);
?>

    <form method="POST" action="" class="form-horizontal" style="width: 60%; margin:30px auto;">
        <div class="control-group">
            <label class="control-label" for="import">Каталог:</label>

            <div class="controls">
                <input type="text" name="import" value="<?php echo $import; ?>"/>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="offers">Цены:</label>

            <div class="controls">
                <input type="text" name="offers" value="<?php echo $offers; ?>"/>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="offers">Режим:</label>

            <div class="controls">
                <label class="radio">
                    <input type="radio" name="mode" value="1" <?php echo $mode1; ?>/>
                    Загрузка каталога
                </label>
                <label class="radio">
                    <input type="radio" name="mode" value="2" <?php echo $mode2; ?>/>
                    Отображение структуры категорий товара
                </label>
                <label class="radio">
                    <input type="radio" name="mode" value="3" <?php echo $mode3; ?>/>
                    Отображение свойств товара
                </label>
                <label class="radio">
                    <input type="radio" name="mode" value="4" <?php echo $mode4; ?>/>
                    Сопостовление категорий
                </label>
            </div>
        </div>
        <div style="text-align:center;">
            <input type="submit" class="btn btn-primary btn-large" name="load" value="Пуск"/>
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

