$(document).ready(function () {
    var change = function ($p, dV) {
        var $i = $p.find('input');
        var $v = $p.find('.km_value');
        var mm = $v.attr('name').split('.');
        var v = parseInt($i.val()) + dV;
        if ((v >= parseInt(mm[0])) && (v <= parseInt(mm[1]))) {
            $i.val(v);
            $v.text(v);
            if ($($i[0]).attr('onchange') == 'menu_onchange(this);')
                menu_onchange($($i)[0]);
        }
    }
    $('body').delegate('.km_uii .km_left', 'click', function () {
        change($(this).parent(), -1);
    });
    $('body').delegate('.km_uii .km_right', 'click', function () {
        change($(this).parent(), 1);
    });
});

function getCaptcha(e) {
    d = new Date();
    $(e).attr("src", "/images/captcha.jpg?" + d.getTime());
}

function validator(form, errClass) {
    var form = (typeof(form) === "string") ? $(form) : form;
    var i = 0;
    while (!form.is('form') && i < 5) {
        i++;
        form = form.parent();
    }
    errClass = errClass || 'formee-error';
    var errCount = 0;
    $('.required', form).each(function (n, elem) {
        if ($(elem).val() == '') {
            errCount += 1;
            $(elem).addClass(errClass);
        }
    });
    if (errCount == 0) return true;
    return false;
}

function sendForm(e, module, controller, action) {
    if (!module) module = 'module';
    if (!controller) controller = 'controller';
    if (!action) action = 'action';
    var tmp = $(e).parent();
    var i = 0;

    while (!tmp.find('form').length && i < 10) {
        tmp = tmp.parent();
        i++;
    }
    var form = tmp.find('form');
    if (validator(form)) {
        url = '/?mode=ajax&module=' + module + '&controller=' + controller + '&action=' + action;
        $.ajax({
            type: "POST",
            url: url,
            data: form.serialize(),
            success: function (data) {
                var answer = $.parseJSON(data);
                alert(answer.text);
                if (answer.refresh) {
                    location.reload();
                }
            }
        })
    } else {
        alert('Заполните все отмеченные поля');
    }
}
