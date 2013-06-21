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

$import = ($_POST['import']) ? $_POST['import'] : '/tmp/1c/import.xml';
$offers = ($_POST['offers']) ? $_POST['offers'] : '/tmp/1c/offers.xml';

if (isset($_POST['load'])) {
    $priceId = $_POST['priceId'];
    $mode = intval($_POST['mode']);
    switch ($mode) {
        case 1:
            $base->loadBase($import, $offers, $priceId);
            break;
        case 2:
            $base->showCategories($import, $offers, $priceId);
            break;
        case 3:
            $base->showProperties($import, $offers, $priceId);
            break;
        case 4:
            $base->loadBase($import, $offers, $priceId, true);
            break;
    }
    $modeName = 'mode' . $mode;
    $$modeName = 'checked';
}
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
                    <input type="radio" name="mode" value="4" <?php echo $mode4; ?>/>
                    Загрузить товары с картинками
                </label>
                <label class="radio">
                    <input type="radio" name="mode" value="2" <?php echo $mode2; ?>/>
                    Отображение структуры категорий товара
                </label>
                <label class="radio">
                    <input type="radio" name="mode" value="3" <?php echo $mode3; ?>/>
                    Отображение свойств товара
                </label>
            </div>
        </div>
        <div style="text-align:center;">
            <input type="submit" class="btn btn-primary btn-large" name="load" value="Пуск"/>
        </div>
    </form>
