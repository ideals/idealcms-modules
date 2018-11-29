<?php
$config = \Ideal\Core\Config::getInstance();
?>
<div class="modal fade" id="modalUpdate">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" disabled="disabled" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title">Диалог обновления 1C</h4>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-loading" style="text-align: center;">
                <img src="/<?=$config->cmsFolder?>/Mods/Shop/Structure/Service/Load1cV2/loading.gif" style="width: 85px;">
            </div>
            <div class="modal-footer">
                <button type="button" disabled="disabled" class="btn btn-default btn-close" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
<form action="" method=post enctype="multipart/form-data">

    <?php
    $config = \Ideal\Core\Config::getInstance();
    $file = new \Ideal\Structure\Service\SiteData\ConfigPhp();
    $cmsFolderPath = DOCUMENT_ROOT . DIRECTORY_SEPARATOR . $config->cmsFolder . DIRECTORY_SEPARATOR;
    $settingsFilePath = $cmsFolderPath . 'load1CV3Settings.php';

    // Если нет файла в папке админки, то копируем его туда из папки модуля
    if (!file_exists($settingsFilePath)) {
        $settingsFilePathMod = $cmsFolderPath . 'Mods/Shop/Structure/Service/Load1CV3/load1CV3Settings.php';
        if (!file_exists($settingsFilePathMod)) {
            // Если файла настроек нет и в папке модуля то выбрасываем исключение
            throw new \RuntimeException('Отсутствует файл настроек модуля выгрузки');
        }
        copy($settingsFilePathMod, $settingsFilePath);
    }

    $file->loadFile($settingsFilePath);
    if (isset($_POST['edit'])) {
        $file->changeAndSave($settingsFilePath);
    }
    echo $file->showEdit();
    ?>
    <div class="form-inline">
        <div class="col-sm-offset-2 col-sm-10">
            <button type="submit" class="btn btn-success pull-right" id="load1c">
                Запустить выгрузку
            </button>
            <button type="submit" class="btn btn-success pull-right" id="resizer" style="margin-right: 5px">
                Запустить ресайз картинок
            </button>
            <input type="submit" class="btn btn-info pull-right" name="edit" value="Сохранить настройки" style="margin-right: 5px"/>
        </div>
    </div>
</form>

<script type="text/javascript">
    var modal_body = $('.modal-body'),
        modal = $('#modalUpdate');

    (function($) {
        $('#load1c').on('click', function(e) {
            modal_body.html('');
            e.preventDefault();
            startPprocess('');
        });
        $('#resizer').on('click', function(e) {
            modal_body.html('');
            e.preventDefault();
            startPprocess('', true);
        });
    }) (jQuery);

    function startPprocess(file, onlyImageResize) {
        if (onlyImageResize === undefined) {
            onlyImageResize = false;
        }

        // Получаем файл для обработки и заголовок к нему
        var url = window.location.href;
        url += '&action=getFile&controller=Shop\\Structure\\Service\\Load1CV3&mode=ajax&file=' + file +
            '&onlyImageResize=' + onlyImageResize;
        modal.modal('show');
        modal.find('.modal-loading').show();
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                filename: file
            },
            success: function (data) {
                var errorResponse = false;
                try {
                    data = JSON.parse(data);
                } catch (err) {
                    var errText = '<div class="alert alert-danger fade in">Ошибка на этапе определения файла для ' +
                        'обработки<br />';
                    errText += data + '</div>';
                    modal_body.append(errText);
                    modal.find('.modal-loading').hide();
                    modal.find('.close, .btn-close').removeAttr('disabled');
                    errorResponse = true;
                }
                if (errorResponse == false) {
                    if (data['filename'] != '') {
                        modal_body.append('<div class="alert alert-info fade in">' +
                            data['response']['infoText'] + '</div>');
                        import1c(data['filename'], data['workDir'], onlyImageResize);
                    } else {
                        modal_body.append('<div class="alert alert-info fade in">Завершение выгрузки</div>');
                        modal_body.append('<div class="alert alert-success fade in">Выгрузка завершена успешно' +
                            '</div>');
                        modal.find('.close, .btn-close').removeAttr('disabled');
                        modal.find('.modal-loading').hide();
                    }
                }
            },
            error: function() {
                modal.find('.close, .btn-close').removeAttr('disabled');
                modal_body.append('<div class="alert alert-danger fade in">Не удалось произвести обновление</div>');
                modal.find('.modal-loading').hide();
            }
        });
    }

    function import1c(file, workDir, onlyImageResize) {
        if (onlyImageResize === undefined) {
            onlyImageResize = false;
        }
        var url = window.location.href;
        url += '&action=importFile&controller=Shop\\Structure\\Service\\Load1CV3&mode=ajax&file=' + file;
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                filename: file,
                workDir: workDir,
                onlyImageResize: onlyImageResize

            },
            success: function (data) {
                var errorResponse = false;
                try {
                    data = JSON.parse(data);
                } catch (err) {
                    var errText = '<div class="alert alert-danger fade in">Не удалось произвести обновление<br />';
                    errText += data + '</div>';
                    modal_body.append(errText);
                    modal.find('.modal-loading').hide();
                    modal.find('.close, .btn-close').removeAttr('disabled');
                    errorResponse = true;
                }
                if (errorResponse == false) {
                    if (data['errors'].length > 0) {
                        var anotherErrText = '<div class="alert alert-danger fade in">';
                        anotherErrText += 'При обновлении произошли ошибки, обновление прекращено:</div>';
                        modal_body.append(anotherErrText);
                        for (var i in data['response']['errors']) {
                            if (!data['response']['errors'].hasOwnProperty(i)) {
                                continue;
                            }
                            var addErrText = '<div class="alert alert-danger fade in">' + data['response']['errors'][i];
                            addErrText += '<br /></div>';
                            modal_body.append(addErrText);
                            modal.find('.close, .btn-close').removeAttr('disabled');
                            modal.find('.modal-loading').hide();
                        }
                    } else {
                        if (data['response'][0] == 'success') {
                            modal_body.append('<div class="alert alert-success fade in">' +
                                data['response']['successText'] + '</div>');
                            startPprocess(data['filename'], onlyImageResize);
                        } else {
                            var failureText = '<div class="alert alert-danger fade in">' +
                                data['response']['successText'] + '</div>';
                            modal_body.append(failureText);
                            modal.find('.close, .btn-close').removeAttr('disabled');
                            modal.find('.modal-loading').hide();
                        }
                    }
                }
            },
            error: function() {
                modal.find('.close, .btn-close').removeAttr('disabled');
                modal_body.append('<div class="alert alert-danger fade in">Не удалось произвести обновление</div>');
                modal.find('.modal-loading').hide();
            }
        });
    }
</script>
