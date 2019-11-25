function setCookie(name, value, path) {
    var cookieString = name + '=' + encodeURIComponent(value);
    if (path) {
        cookieString += '; path=' + encodeURIComponent(path);
    } else {
        cookieString += '; path=/';
    }
    document.cookie = cookieString;
}

function deleteCookie(name) {
    var cookieDate = new Date();  // Текущая дата и время
    cookieDate.setTime(cookieDate.getTime() - 1);
    name += '=; expires=' + cookieDate.toUTCString();
    document.cookie = name;
}

function getCookie(name) {
    var results = document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)');

    if (results) {
        return (decodeURIComponent(results[2]));
    } else {
        return null;
    }
}

Number.prototype.format = function (n, x, s, c) {
    var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\D' : '$') + ')';
    var num = this.toFixed(Math.max(0, ~~n));

    return (c ? num.replace('.', c) : num).replace(new RegExp(re, 'g'), '$&' + (s || ','));
};

jQuery.fn.hasAttr = function(name) {
    return this.attr(name) !== undefined;
};

function loadBasket(basket) {
    var $cart = jQuery('.cart-min-desc');
    if (basket.count > 0) {
        var total = parseInt(basket.total) / 100;
        var str = basket.count + ' продукт(ов):<br/>' + total.format(2, 3, ',', '.') + ' руб.';
        $cart.html(str);
    } else {
        $cart.text('(Пусто)');
    }
}

function fastAddGood(e) {
    var $this = jQuery(e);
    var onclick = $this.attr('onclick');
    $this.attr('onclick', 'return false');
    jQuery.ajax({
        type: 'POST',
        data: 'count=+1&good-id=' + $this.attr('data-id'),
        url: '/?mode=ajax&controller=Shop\\Structure\\Basket\\Site&action=addGood',
        dataType: 'json',
        success: function (data) {
            deleteCookie('basket');
            setCookie('basket', JSON.stringify(data.basket, null, 2));
            if (data.text.length > 0) {
                alert(data.text);
            }
            loadBasket(data.basket);
            $this.parents('.product-wrapper').append('<span class="added" style="display: inline;">Добавлено</span>');
        },
        error: function (data) {
            console.log(data);
        }
    });
    $this.attr('onclick', onclick);
}

function fastChangeQuant(e) {
    var $this = jQuery(e);
    var onclick = $this.attr('onclick');
    $this.attr('onclick', ' return false');
    var count = ($this.hasAttr('data-count')) ? $this.hasAttr('data-count') : $this.val();
    jQuery.ajax({
        type: 'POST',
        data: 'count=' + count + '&good-id=' + $this.attr('data-id'),
        url: '/?mode=ajax&controller=Shop\\Structure\\Basket\\Site&action=quantGood',
        dataType: 'json',
        success: function (data) {
            deleteCookie('basket');
            setCookie('basket', JSON.stringify(data.basket, null, 2));
            loadBasket(data.basket);
        }
    });
}

function fastDelGood(e) {
    var $this = jQuery(e);
    var onclick = $this.attr('onclick');
    $this.attr('onclick', 'return false');
    jQuery.ajax({
        type: 'POST',
        data: 'count=0&good-id=' + $this.attr('data-id'),
        url: '/?mode=ajax&controller=Shop\\Structure\\Basket\\Site&action=addGood',
        async: false,
        dataType: 'json',
        success: function (data) {
            if (!data.error) {
                deleteCookie('basket');
                setCookie('basket', JSON.stringify(data.basket, null, 2));
                loadBasket(data.basket);
                $this.parent().parent().remove();
            }
        }
    });
    return 'true';
}

jQuery(document).ready(function ($) {
    jQuery.ajax({
        type: 'POST',
        url: '/?mode=ajax&controller=Shop\\Structure\\Basket\\Site&action=getBasket',
        dataType: 'json',
        success: function (data) {
            // TODO уточнить необходимость удаления перед записью значения, перезапись работает и без этого
//            deleteCookie('basket');
            setCookie('basket', JSON.stringify(data.basket, null, 2));
            loadBasket(data.basket);
        },
        error: function (data) {
            console.log(data);
        }
    });
});
