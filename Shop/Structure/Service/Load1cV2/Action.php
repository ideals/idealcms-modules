<?php
$config = \Ideal\Core\Config::getInstance();
?>
<div class="modal fade" id="modalUpdate">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" disabled="disabled" data-dismiss="modal"><span aria-hidden="true">&times;</span><span
                            class="sr-only">Close</span></button>
                <h4 class="modal-title">Диалог обновления 1C</h4>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-loading" style="text-align: center;">
                <img src="/<?= $config->cmsFolder ?>/Mods/Shop/Structure/Service/Load1cV2/loading.gif"
                     style="width: 85px;">
            </div>
            <div class="modal-footer">
                <button type="button" disabled="disabled" class="btn btn-default btn-close" data-dismiss="modal">
                    Закрыть
                </button>
            </div>
        </div>
    </div>
</div>
<form action="" method=post enctype="multipart/form-data">

    <?php
    $config = \Ideal\Core\Config::getInstance();
    $file = new \Ideal\Structure\Service\SiteData\ConfigPhp();
    $cmsFolderPath = DOCUMENT_ROOT . DIRECTORY_SEPARATOR . $config->cmsFolder . DIRECTORY_SEPARATOR;
    $settingsFilePath = $cmsFolderPath . 'load1cV2Settings.php';

    // Если нет файла в папке админки, то копируем его туда из папки модуля
    if (!file_exists($settingsFilePath)) {
        $settingsFilePathMod = $cmsFolderPath . 'Mods/Shop/Structure/Service/Load1cV2/load1cV2Settings.php';
        if (!file_exists($settingsFilePathMod)) {
            // Если файла настроек нет и в папке модуля то выбрасываем исключение
            throw new \Exception('Отсутствует файл настроек модуля выгрузки');
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
            <input type="submit" class="btn btn-info pull-right" name="edit" value="Сохранить настройки"
                   style="margin-right: 5px"/>
        </div>
    </div>
</form>

<script type="text/javascript">
    var modal_body = $('.modal-body'),
        modal = $('#modalUpdate');

    (function ($) {
        $('#load1c').on('click', function (e) {
            modal_body.html('');
            e.preventDefault();
            load1c();
        });
        $('#resizer').on('click', function (e) {
            modal_body.html('');
            e.preventDefault();
            load1c(6, 1, true);
        });
    })(jQuery);

    function load1c(step, packageNum, fixStep) {
        step = step || 1;
        packageNum = packageNum || 1;
        fixStep = fixStep || false;
        var url = window.location.href + "&action=ajaxIndexLoad&controller=Shop\\Structure\\Service\\Load1cV2&mode=ajax";
        modal.modal('show');
        modal.find('.modal-loading').show();

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                step: step,
                packageNum: packageNum,
                fixStep: fixStep
            },
            success: function (data) {
                var errorResponse = false;
                try {
                    data = JSON.parse(data);
                } catch (err) {
                    modal_body.append('<div class="alert alert-danger fade in">Не удалось произвести обновление<br />' + data + '</div>');
                    modal.find('.modal-loading').hide();
                    modal.find('.close, .btn-close').removeAttr('disabled');
                    errorResponse = true;
                }
                if (errorResponse == false) {
                    modal_body.append('<div class="alert alert-info fade in">' +
                        data['infoText'] + '</div>');

                    if (data['errors'].length > 0) {
                        modal_body.append('<div class="alert alert-danger fade in">При обновлении произошли ошибки, обновление прекращено:</div>');
                        for (var i in data['errors']) {
                            if (!data['errors'].hasOwnProperty(i)) {
                                continue;
                            }
                            modal_body.append('<div class="alert alert-danger fade in">' + data['errors'][i] + '<br /></div>');
                            modal.find('.close, .btn-close').removeAttr('disabled');
                            modal.find('.modal-loading').hide();
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
                                modal.find('.modal-loading').hide();
                            }
                        }
                    }
                }
            },
            error: function () {
                modal.find('.close, .btn-close').removeAttr('disabled');
                modal_body.append('<div class="alert alert-danger fade in">Не удалось произвести обновление</div>');
                modal.find('.modal-loading').hide();
            }
        });
    }
</script>
