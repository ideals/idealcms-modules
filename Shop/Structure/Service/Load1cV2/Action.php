<form action="" method=post enctype="multipart/form-data">

<?php
    $config = \Ideal\Core\Config::getInstance();
    $file = new \Ideal\Structure\Service\SiteData\ConfigPhp();

    $file->loadFile('Shop/Structure/Service/Load1cV2/load1cV2Settings.php');
    if (isset($_POST['edit'])) {
        $file->changeAndSave('Shop/Structure/Service/Load1cV2/load1cV2Settings.php');
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
            load1c();
        });
        $('#resizer').on('click', function(e) {
            modal_body.html('');
            e.preventDefault();
            load1c(6);
        });
    }) (jQuery);

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
