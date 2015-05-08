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

function loadBasket(basket) {
    var $count = jQuery('.cart-items-number');
    var $price = jQuery('.cart-subtotal > .amount');
    $count.text(basket.count);
    $price.text((parseInt(basket.total) / 100).format(2, 3, ',', '.'));
}

function fastAddGood(e) {
    var $this = jQuery(e);
    var onclick = $this.attr('onclick');
    $this.attr('onclick', 'return false');
    jQuery.ajax({
        type: 'POST',
        data: 'quantity=1&add-to-cart=' + $this.attr('data-id'),
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
    var quant = ($this.hasAttr('data-count')) ? $this.hasAttr('data-count') : $this.val();
    jQuery.ajax({
        type: 'POST',
        data: 'quantity=' + quant + '&add-to-cart=' + $this.attr('data-id'),
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
        data: 'add-to-cart=' + $this.attr('data-id'),
        url: '/?mode=ajax&controller=Shop\\Structure\\Basket\\Site&action=delGood',
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
}

jQuery(document).ready(function ($) {
    jQuery.ajax({
        type: 'POST',
        url: '/?mode=ajax&controller=Shop\\Structure\\Basket\\Site&action=getBasket',
        dataType: 'json',
        success: function (data) {
            deleteCookie('basket');
            setCookie('basket', JSON.stringify(data.basket, null, 2));
            loadBasket(data.basket);
        },
        error: function (data) {
            console.log(data);
        }
    });
});
