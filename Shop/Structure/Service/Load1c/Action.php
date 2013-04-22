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
            break;
        case '3':
            $base->showProperties($import, $offers, $priceId);
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
        <div class="control-group">
            <label class="control-label" for="smallimg">Размер маленькой картинки:</label>

            <div class="controls">
                <input type="text" name="smallimg" value="<?php echo $ini['smallimg']; ?>"/>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="bigimg">Размер большой картинки:</label>

            <div class="controls">
                <input type="text" name="bigimg" value="<?php echo $ini['bigimg']; ?>"/>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="water">Надпись на картинке:</label>

            <div class="controls">
                <input type="text" name="water" value="<?php echo $ini['water']; ?>"/>
            </div>
        </div>
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

