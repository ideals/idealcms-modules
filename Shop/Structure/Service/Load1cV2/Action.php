<?php
include('modalUpdate.html');
if (isset($item['info']['enable_zip'])) {
    if ($item['info']['enable_zip'] == 'yes') {
        $item['info']['enable_zip'] = 'checked="checked"';
    } else {
        $item['info']['enable_zip'] = '';
    }
}
if (isset($item['info']['keep_log'])) {
    if ($item['info']['keep_log'] == 'yes') {
        $item['info']['keep_log'] = 'checked="checked"';
    } else {
        $item['info']['keep_log'] = '';
    }
}
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
        <label class="col-sm-2 control-label" for="filesize">Максимальный размер файла в Мб:</label>

        <div class="col-sm-10">
            <input class="form-control" name="filesize"
                   value="<?=$item['info']['filesize']?>" type="text">
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label" for="enable_zip" title="Включите архивирование при 'больших' выгрузках">Разрешить архивирование):</label>

        <div class="col-sm-10">
            <input class="form-control" name="enable_zip"
                   value="" type="checkbox" <?=$item['info']['enable_zip']?>>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label" for="enable_zip" title="Осуществлять логирование">Осуществлять логирование:</label>

        <div class="col-sm-10">
            <input class="form-control" name="keep_log"
                   value="" type="checkbox" <?=$item['info']['keep_log']?>>
        </div>
    </div>

    <div class="form-inline">
        <div class="col-sm-offset-2 col-sm-10">
            <button type="submit" class="btn btn-success pull-right" id="load1c">
                Запустить выгрузку
            </button>
            <button type="submit" class="btn btn-success pull-right" id="resizer" style="margin-right: 5px">
                Запустить ресайз картинок
            </button>
            <button type="submit" class="btn btn-primary pull-right" id="save_settings" style="margin-right: 5px">
                Сохранить настройки
            </button>
        </div>
    </div>
</form>

<script type="text/javascript">
    var modal_body = $('.modal-body'),
        modal = $('#modalUpdate');

    (function($) {
        $('#save_settings').on('click', function(e) {
            e.preventDefault();
            saveSettings();
        });
        $('#load1c').on('click', function(e) {
            modal_body.html('');
            e.preventDefault();
            load1c();
        });
        $('#resizer').on('click', function(e) {
            modal_body.html('');
            e.preventDefault();
            load1c(6);
        });
    }) (jQuery);

    // Сохраненяет текст комментария из модального окна
    function saveSettings() {
        var
            url = window.location.href + "&action=ajaxUpdateSettings&controller=Shop\\Structure\\Service\\Load1cV2&mode=ajax",
            data = {};

        $('.form-horizontal input[type="text"]').each(function(k, val) {
            data[val.name] = val.value;
        });
        $('.form-horizontal input[type="checkbox"]').each(function(k, val) {
            if ($(val).is(':checked')) {
                data[val.name] = 1;
            } else {
                data[val.name] = 0;
            }
        });

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            success: function (data) {
                location.reload();
            }
        });
    }

    function load1c(step, packageNum, fixStep) {
        step = step || 1;
        packageNum = packageNum || 1;
        fixStep = fixStep || false;
        var url = window.location.href + "&action=ajaxIndexLoad&controller=Shop\\Structure\\Service\\Load1cV2&mode=ajax";
        modal.modal('show');

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                step: step,
                packageNum: packageNum,
                fixStep: fixStep
            },
            success: function (data) {
                data = JSON.parse(data);
                modal_body.append('<div class="alert alert-info fade in">' +
                    data['infoText'] + '</div>');

                if (data['errors'].length > 0) {
                    modal_body.append('<div class="alert alert-danger fade in">При обновлении произошли ошибки, обновление прекращено:</div>');
                    for (var i in data['errors']) {
                        if (!data['errors'].hasOwnProperty(i)) {
                            continue;
                        }
                        modal_body.append('<div class="alert alert-danger fade in">' + data['errors'][i] + '<br /></div>');
                    }
                } else {
                    delete data['errors'];

                    if ('offer' in data) {
                        for (var i in data) {
                            if (
                                !data.hasOwnProperty(i)
                                || i == 'continue'
                                || i == 'step'
                                || i == 'nextStep'
                                || i == 'infoText'
                            ) {
                                continue;
                            }
                            modal_body.append('<div class="alert alert-info fade in">' +
                                data[i]['infoText'] + '</div>');
                            modal_body.append('<div class="alert alert-success fade in">' +
                                data[i]['successText'] + '</div>');
                        }
                    } else {
                        modal_body.append('<div class="alert alert-success fade in">' +
                            data['successText'] + '</div>');
                    }

                    if (typeof data['nextStep'] != 'undefined' && fixStep == false) {
                        step = data['nextStep'];
                    }
                    if (typeof data['packageNum'] != 'undefined') {
                        packageNum = data['packageNum'];
                    }

                    if (data['continue']) {
                        load1c(step, packageNum);
                    } else {
                        if (data['repeat']) {
                            load1c(step, packageNum)
                        } else {
                            modal.find('.close, .btn-close').removeAttr('disabled');
                        }
                    }
                }
            },
            error: function() {
                modal_body.append('<div class="alert alert-danger fade in">Не удалось произвести обновление</div>');
            }
        });
    }
</script>