<?php
namespace Shop\Structure\Order\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;

class AjaxController extends \Ideal\Core\Site\AjaxController
{

    public function testAction()
    {
        $basket = $_POST['cookie'];
        $basket = json_decode($basket, true);
        if ($basket['count'] <= 0) {
            return;
        }
        $params = array();
        parse_str($_POST['form'], $params);

        $address = @ htmlspecialchars($params['address']);
        $delivery = @ htmlspecialchars($params['delivery']);
        $payment = @ htmlspecialchars($params['payment']);
        $name = @ htmlspecialchars($params['name']);
        $email = @ htmlspecialchars($params['email']);
        $phone = @ htmlspecialchars($params['phone']);
        $message = @ htmlspecialchars($params['message']);
        $db = Db::getInstance();

        $in = "(";
        foreach ($basket as $key => $value) {
            if ($key == 'count' OR $key == 'total_price') continue;
            $in .= $key . ',';
        }
        $in = substr($in, 0, strlen($in) - 1);
        $in .= ")";

        $_sql = "SELECT ID,name,url FROM i_shop_structure_good WHERE id IN {$in}";
        $goodIdsArr = $db->queryArray($_sql);
        $date = time();
        $insert['date_create'] = $date;
        $insert['date_mod'] = $date;
        $insert['address'] = $address;
        $insert['price'] = $basket['total_price'];
        $insert['name'] = $name;
        $insert['stock'] = $basket['count'];
        $insert['structure_path'] = 8;
        $insert['is_active'] = 1;
        $id = $db->insert("i_shop_structure_order", $insert);

        $mail = <<<EOT
<div>
<div>
<div>
<table>
<tbody><tr>
<td align="left" valign="center"><img></td>
<td align="left" valign="center">
<h2 style="color:#daa520;font-variant:small-caps">Международный Центр Профилактики Онкологических Заболеваний</h2>
<br>
<table>
<tbody><tr><td align="center" valign="top"><span>+7(812)6400675</span><br><span>+7(495)6400675</span></td>
<td align="left" valign="top">
Санкт-Петербург<br>
пл. Александра Невского, 2, лит.Е,<br>
БЦ "Москва" офис 806
</td>
</tr></tbody></table>
<p></p>
</td>
</tr>
</tbody></table>

<h1 style="color:#43a8d2">Ваш заказ принят.</h1>


<p style="color:#575757">Здравствуйте, $name, вашему заказу присвоен номер <b>$id</b></p>

<p style="color:#575757">Сегодня или в ближайший рабочий день по телефону $phone Вам позвонит наш сотрудник.</p>

<p style="color:#575757">C Вами согласуют <b>адрес доставки</b>, ФИО получателя, уточнят <b>наличие товаров</b> на
складе, рассчитают <b>стоимость/сроки доставки</b> и  пришлют <b>данные для оплаты</b> заказа.</p>

<p style="color:#575757">Вы выбрали способ оплаты:Банковский перевод<br>
Пожалуйста не оплачивайте заказ без согласования с нашим сотрудником.</p>

<p>
<font color="red">Важно!</font><br>
Если продукция в Вашем заказе предназначена для больного раком, то настоятельно рекомендуем заполнить
<a href="http://mcpoz.ru/anketa" target="_blank">анкету больного раком</a> на нашем сайте
(<a href="http://mcpoz.ru/anketa" target="_blank">нажмите сюда</a>). Cпециалисты нашего Центра составят ИНДВИДУАЛЬНУЮ
 программу приема препаратов и рекомендации по получению максимального эффекта.
<br>
Программа составляется с учетом диагноза, возраста и других индвидуальных особенностей больного раком.</p>


<p>Состав заказа № $id:</p>

<ul>
EOT;

        foreach ($goodIdsArr as $good) {
            $gName = $good["name"];
            $gUrl = "http://mcpoz.ru/goods/" . $good["url"];
            $amount = $basket[$good['ID']]['amount'];
            $price = $basket[$good['ID']]['price'];
            $gPrice = $amount * $price;
            $mail .= '<li>';
            $mail .= "<b><a href='$gUrl' target='_blank'>$gName</a> $price руб x $amount шт = $gPrice руб.</b>";
            $mail .= '</li>';
        }


        $mail .= <<<EOT
</ul>

<p><b>На сумму {$basket['total_price']} руб</b></p>

<p>Данные покупателя:</p>

<table cellpadding="3">
  <tbody><tr><td>Адрес:</td><td>$address</td></tr>
  <tr><td>Способ доставки:</td><td>$delivery</td></tr>
  <tr><td>Способ оплаты:</td><td>$payment</td></tr>
  <tr><td>Ф.И.О.:</td><td>$name</td></tr>
  <tr><td>E-mail:</td><td>$email</a><br></td></tr>
  <tr><td>Телефон:</td><td>$phone</td></tr>
  <tr><td>Сообщение:</td><td>$message</td></tr>
</tbody></table>


<p style="color:#575757">Спасибо за обращение в Международный Центр Профилактики Онкологических Заболеваний.</p>
<p style="color:#575757">Здоровья Вам и Вашим близким!</p>
</div>
</div>
</div>
EOT;

        $subject = 'Заказ товара с mcpoz.ru';
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset="utf-8"' . "\r\n";

// Дополнительные заголовки
        $headers .= 'From: order@mcpoz.ru' . "\r\n";

// Отправляем
        if ($email != '') {
            // Если указано мыло - отправляем уведомление покупателю
            mail($email, $subject, $mail, $headers);
        }
        $headers .= "Bcc: top@neox.ru, help1@neox.ru\r\n";
        mail('vitaminb17@mail.ru', $subject, $mail, $headers);
        $tmp['content'] = $mail;
        $tmp['is_active'] = 1;
        $db->update("i_shop_structure_order", $id, $tmp);
        print $mail;
    }


    public function orderAction()
    {
        $answer = array('error' => 0, 'text' => '');
        $db = Db::getInstance();
        $config = Config::getInstance();
        $email = @ htmlspecialchars($_POST['email']);
        $fio = @ htmlspecialchars($_POST['fio']);
        $emailPass = '';
        $password = '';
        session_start();
        if (!isset($_SESSION['userChecked']))
            if ($_SESSION['userChecked'] != true) {
                $chars = "qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
                $max = 10;
                $size = strlen($chars) - 1;
                while ($max--)
                    $password .= $chars[rand(0, $size)];
                $emailPass = "<span>Пароль: </span><span>{$password}</span><br/>";
            } else {
                unset($_SESSION['userChecked']);
            }
        unset($_SESSION['userChecked']);
        $phone = @ htmlspecialchars($_POST['phone']);
        $postcode = @ htmlspecialchars($_POST['postcode']);
        $city = @ htmlspecialchars($_POST['city']);
        $address = @ htmlspecialchars($_POST['address']);
        $comment = @ htmlspecialchars($_POST['comment']);

        $noneReg = false;
        /*if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $answer['text'] .= "Не верный E-mail\n";
            //$answer['error'] = 1;
        }*/
        if ($answer['error'] == 0) {
            $basketModel = new Basket\Site\Model('');
            $goods = $basketModel->getGoods();
            $table = $config->db['prefix'] . 'cabinet_structure_user';
            $tmp = $db->queryArray("SELECT ID FROM {$table} WHERE email='{$email}' LIMIT 1");
            if (count($tmp) > 0) {
                $id = $tmp[0]['ID'];
                $noneReg = true;
                //$answer['text'] = 'Данный E-mail уже ранее использовался, можете загрузить данные';
            } else {
                $prev_structure = $config->getStructureByName('Cabinet_User');
                $prev_structure = '0-' . $prev_structure['ID'];
                $id = $db->insert($config->db['prefix'] . 'cabinet_structure_user', array(
                    'email' => $email,
                    'fio' => $fio,
                    'password' => md5($password),
                    'phone' => $phone,
                    'postcode' => $postcode,
                    'city' => $city,
                    'address' => $address,
                    'is_active' => 1,
                    'prev_structure' => $prev_structure,
                    'reg_date' => time()
                ));
            }

            $order = new Order\Site\Model('');
            $idOrder = $order->createOrder($comment, $fio, $city . ', ' .$address);

            $mailHeader = <<<HEADER
    <head>
	<style type="text/css">
        body, td, span, p, th {padding:10px}
        .mail-table {margin: auto;width:600px;}
        .user-fields {width:50%;vertical-align:top;}
        table.html-email-top {margin:10px auto;background:#fff;width:600px;}
	    table.html-email {margin:10px auto;background:#fff;border-color: #dad8d8;border-width:1px;border-style:solid;width:600px;}
	    .html-email tr{border-color: #eeeeee;border-width:1px;border-style:solid;}
        .th-left, .th-center {border-color: #dad8d8;border-width:1px;border-style:solid;}
        .td-left, .td-center {border-color: #dad8d8;border-width:1px;border-style:solid;}
        .th-left {width:50%;}
        .th-center {width:20%;}
        .th-right {width:30%;}
        .td-left, .td-center, .td-right {vertical-align:top;}
        .order-info {width:50%;}
	    span.grey {color:#666;}
	    span.date {color:#666; }
	    a.default:link, a.default:hover, a.default:visited {color:#666;line-height:25px;background: #f2f2f2;margin: 10px ;padding: 3px 8px 1px 8px;border: solid #CAC9C9 1px;border-radius: 4px;-webkit-border-radius: 4px;-moz-border-radius: 4px;text-shadow: 1px 1px 1px #f2f2f2;font-size: 12px;background-position: 0px 0px;display: inline-block;text-decoration: none;}
	    a.default:hover {color:#888;background: #f8f8f8;}
	    .cart-summary{ }
	    .html-email th { background: #ccc;margin: 0px;padding: 10px;text-align:left;}
	    .sectiontableentry2, .html-email th, .cart-summary th{ background: #ccc;margin: 0px;padding: 10px;}
	    .sectiontableentry1, .html-email td, .cart-summary td {background: #fff;margin: 0px;padding: 10px;}
	    .line-through{text-decoration:line-through}
	</style>
    </head>
HEADER;


            $orderMail = <<<LETTER
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    {$mailHeader}
    <body>
	<div class="mail-table">
			<table width="100%" border="0" cellpadding="0" cellspacing="0" class="html-email">
    <tr>
    <td>
        <strong>Здравствуйте!</strong><br/>
</td></tr></table>
<table cellpadding="5" cellspacing="0" class="html-email-top">

  <tr>
    <td class="order-info">
		Номер заказа:<br />
		<b>{$idOrder}</b>

	</td>
    <td class="order-info">
	</td>
  </tr>
  <tr>
  <td colspan="2">
				Статус заказа: В обработке</td>
  </tr>
  <tr>
  <td colspan="2">
    Доставка: <span class="vmshipment_name">Курьерская доставка по Москве</span><br />
    Оплата: <span class="vmpayment_name">Наличными курьеру</span><br /></td>
  </tr>
    </table>
<table class="html-email" cellspacing="0" cellpadding="5" style="border: 1px solid #dad8d8;width:600px;margin:10px 0px 10px 0px;border-collapse: collapse;">
    <tr>
	<th class="th-left" style="border: 1px solid #dad8d8;background-color:#eeeeee;">
	    Товар
	</th>
	<th class="th-center" style="border: 1px solid #dad8d8;background-color:#eeeeee;">
        Цена за шт.
	</th>
    <th class="th-right" style="border: 1px solid #dad8d8;background-color:#eeeeee;">
        Количество
   	</th>
    </tr>
LETTER;
            foreach ($goods['good'] as $k => $v) {
                $orderMail .= <<<LETTER
                <tr>
	                <td class="td-left" valign="top" style="border: 1px solid #dad8d8;">
                        {$v['name']}<br />
                        <span style="font-size:10px;font-style:italic;">
                            <span class="costumTitle">Артикул:</span>&nbsp;
                            <span class="costumValue" >{$v['articul']}</span>&nbsp;
                        </span>
                        <br /><span style="font-size:10px;font-style:italic;">
                            <span class="costumTitle">Размер</span>&nbsp;
                            <span class="costumValue" >{$v['size']}</span>&nbsp;
                        </span>
                    </td>
                    <td class="td-center" valign="top" style="border: 1px solid #dad8d8;">{$v['total_price']} руб.</td>
                    <td class="td-right" valign="top" style="border: 1px solid #dad8d8;">{$v['count']}</td>
                </tr>
LETTER;
            }
            $price = (float)$goods['price'] + 300;
            $orderMail .= <<<LETTER
    </table>
<p>
		Доставка: 300.00 руб.</p>
<p>
		Итого: {$price} руб.</p>
<table class="html-email" cellspacing="0" cellpadding="5" style="border: 1px solid #dad8d8;width:600px;margin:10px 0px 10px 0px;border-collapse: collapse;">  <tr  >
	<th class="user-fields" style="border: 1px solid #dad8d8;background-color:#eeeeee;">
	    Адрес доставки
	</th>
    </tr>
    <tr>
	<td class="user-fields" style="border: 1px solid #dad8d8;">
        <span>Имя:</span>
        <span>{$fio}</span><br />
        <span class="titles">Индекс:</span>
        <span class="values vm2-zip" >{$postcode}</span><br />
		<span class="titles">Город:</span>
		<span class="values vm2-city" >{$city}</span><br />
		<span class="titles">Адрес:</span>
		<span class="values vm2-address_1" >{$address}</span><br />
		<span class="titles">Телефон:</span>
		<span class="values vm2-phone_1" >{$phone}</span><br />
		<span class="titles">email:</span>
		<span class="values vm2-email" >{$email}</span><br />
		<span class="titles">Комментарий:</span>
		<span class="values vm2-phone_1" >{$comment}</span><br />
		</td>
    </tr>
</table>

<br/><br/>Спасибо за покупку в <a href="http://www.ciaokids.ru/">www.ciaokids.ru</a><br/>Ciaokids<br />	</div>
    </body>
</html>
LETTER;


            if ($emailPass !== '') {
                $regMail = <<<LETTER
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    {$mailHeader}

    <body>
	<div class="mail-table">
			<table width="100%" border="0" cellpadding="0" cellspacing="0" class="html-email">
    <tr>
    <td>
        <strong>Здравствуйте!</strong><br/>
</td></tr></table>
    <table class="html-email" cellspacing="0" cellpadding="5" style="border: 1px solid #dad8d8;width:600px;margin:10px 0px 10px 0px;border-collapse: collapse;">  <tr  >
	<th class="user-fields" style="border: 1px solid #dad8d8;background-color:#eeeeee;">
	    Регистрационные данные нового покупателя
	</th>
    </tr>
    <tr>
	<td class="user-fields" style="border: 1px solid #dad8d8;">
        <span>Имя:</span>
        <span>{$fio}</span><br />
        {$emailPass}
        <span class="titles">Индекс:</span>
        <span class="values vm2-zip" >{$postcode}</span><br />
		<span class="titles">Город:</span>
		<span class="values vm2-city" >{$city}</span><br />
		<span class="titles">Адрес:</span>
		<span class="values vm2-address_1" >{$address}</span><br />
		<span class="titles">Телефон:</span>
		<span class="values vm2-phone_1" >{$phone}</span><br />
		<span class="titles">Комментарий:</span>
		<span class="values vm2-phone_1" >{$comment}</span><br />
		</td>
    </tr>
</table>

<br/><br/>Спасибо за покупку в <a href="http://www.ciaokids.ru/">www.ciaokids.ru</a><br/>Ciaokids<br />	</div>
    </body>
</html>
LETTER;
            }

            $order->updateOrder($orderMail, $idOrder, $price);
            $orderTitle = 'Заказ';
            $to = $email;
            $headers = "From: {$config->robotEmail}\r\n"
                . "Content-type: text/html; charset=\"utf-8\"";

            $mailRegSend = true;
            if (($emailPass !== '') && ($noneReg != true)) {
                $regTitle = 'Регистрация';
                $mailRegSend = mail($to, $regTitle, $regMail, $headers);
                $answer['text'] = 'Ошибка регистрации. ';
                $answer['error'] = 1;
            }

            if (mail($to, $orderTitle, $orderMail, $headers) && mail($config->mailForm, $orderTitle, $orderMail, $headers) && $mailRegSend) {
                $answer['text'] = 'Ваш заказ принят в обработку. Наш менеджер скоро с вами свяжется.';
            } else {
                $answer['text'] .= 'Ошибка. Попробуйте чуть позже';
                $answer['error'] = 1;
            }
        }
        print json_encode($answer);
        exit;
    }

}
