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
            <div class="modal-loading" style="text-align: center;"></div>
            <style type="text/css">
                .modal-loading {
                    border: 16px solid #f3f3f3; /* Light grey */
                    border-top: 16px solid #3498db; /* Blue */
                    border-radius: 50%;
                    width: 60px;
                    height: 60px;
                    animation: spin 2s linear infinite;
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
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
                Выгрузка
            </button>
            <button type="submit" class="btn btn-success pull-right" id="resizer" style="margin-right: 5px">
                Ресайз картинок
            </button>
            <button type="submit" class="btn btn-primary pull-right" id="load1c-deactivate" style="margin-right: 5px">
                Выгрузка с деактивацией
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
            startProcess('');
        });
        $('#resizer').on('click', function(e) {
            modal_body.html('');
            e.preventDefault();
            startProcess('', true);
        });
        $('#load1c-deactivate').on('click', function(e) {
            modal_body.html('');
            e.preventDefault();
            startProcess('', false, true);
        });
    }) (jQuery);

    function startProcess(file, onlyImageResize, isDeactivate = true) {
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
                        console.log(data);
                        modal_body.append('<div class="alert alert-info fade in">' +
                            data['response']['infoText'] + '</div>');
                        import1c(data['filename'], data['workDir'], onlyImageResize, isDeactivate);
                    } else {
                        modal_body.append('<div class="alert alert-info fade in">Завершение выгрузки</div>');
                        if (isDeactivate) {
                            deactivate();
                        }
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

    function import1c(file, workDir, onlyImageResize, isDeactivate) {
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
                        anotherErrText += 'При обновлении произошли ошибки:</div>';
                        modal_body.append(anotherErrText);
                        for (var i in data['errors']) {
                            if (!data['errors'].hasOwnProperty(i)) {
                                continue;
                            }
                            var addErrText = '<div class="alert alert-danger fade in">' + data['errors'][i];
                            addErrText += '<br /></div>';
                            modal_body.append(addErrText);
                            modal.find('.close, .btn-close').removeAttr('disabled');
                            modal.find('.modal-loading').hide();
                        }
                        startProcess(data['filename'], onlyImageResize, isDeactivate);
                    } else {
                        if (data['response'][0] == 'success') {
                            modal_body.append('<div class="alert alert-success fade in">' +
                                data['response']['successText'] + '</div>');
                            startProcess(data['filename'], onlyImageResize, isDeactivate);
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

    function deactivate() {
        let url = window.location.href;
        url += '&action=deactivate&controller=Shop\\Structure\\Service\\Load1CV3&mode=ajax';
        $.ajax({
            url: url,
            type: 'POST',
            success: function (dataJson) {
                try {
                    data = JSON.parse(dataJson);
                } catch (err) {
                    showError('Не удалось произвести обновление<br />' + dataJson);
                    return;
                }
                if (data['error'] !== '') {
                    showError(data['error']);
                }
            },
            error: function() {
                showError('Не удалось произвести обновление');
            }
        });
    }

    function showError(text) {
        modal.find('.close, .btn-close').removeAttr('disabled');
        modal_body.append('<div class="alert alert-danger fade in">' + text + '</div>');
        modal.find('.modal-loading').hide();
    }
</script>
