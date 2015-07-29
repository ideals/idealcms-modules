<?php
$a = 1;
?>

<form class="form-horizontal">
    <div class="form-group">
        <label class="col-sm-2 control-label" for="directory">Папка выгрузки файлов:</label>

        <div class="col-sm-10">
            <input class="form-control" name="directory"
                   value="<?=$item['info']['directory']?>" type="text">
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label" for="images_directory">Каталог изображений:</label>

        <div class="col-sm-10">
            <input class="form-control" name="images_directory"
                   value="<?=$item['info']['images_directory']?>" type="text">
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label" for="resize">Значение ресайза изображения:</label>

        <div class="col-sm-10">
            <input class="form-control" name="resize"
                   value="<?=$item['info']['resize']?>" type="text">
        </div>
    </div>

    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
            <button type="submit" class="btn btn-primary btn-large" id="save_settings">Сохранить</button>
        </div>
    </div>
</form>

<script type="text/javascript">
    (function($) {
        $('#save_settings').on('click', function(e) {
            e.preventDefault();
            saveSettings();
        });
    }) (jQuery);

    // Сохраненяет текст комментария из модального окна
    function saveSettings() {
        var
            url = window.location.href + "&action=ajaxUpdateSettings",
            data = {};

        $('.form-horizontal input[type="text"]').each(function(k, val) {
            data[val.name] = val.value;
        });

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            success: function (data) {
                console.log(data);
            }
        })
    }
</script>