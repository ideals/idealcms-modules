<?php
namespace Shop\Structure\Order\Site;

use Ideal\Core\Db;

class AjaxController extends \Ideal\Core\Site\AjaxController
{

    public function testAction()
    {
        $address = $_POST['address'];
        $delivery = $_POST['delivery'];
        $payment = $_POST['payment'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $message = $_POST['message'];
        $basket = $_COOKIE['basket'];
        $db = Db::getInstance();


        $basket = json_decode($basket, true);
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
        $insert['is_active'] = 0;
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

$date
<p>
<font color="red">Важно!</font><br>
Если продукция в Вашем заказе предназначена для больного раком, то настоятельно рекомендуем заполнить
<a href="http://mcpoz.ru/anketa" target="_blank">анкету больного раком</a> на нашем сайте
(<a href="http://mcpoz.ru/anketa" target="_blank">нажмите сюда</a>). Cпециалисты нашего Центра составят ИНДВИДУАЛЬНУЮ
 программу приема препаратов и рекомендации по получению максимального эффекта.
<br>
Программа составляется с учетом диагноза, возраста и других индвидуальных особенностей больного раком.</p>



Состав заказа № $id:
<br><br>
EOT;

        foreach ($goodIdsArr as $good) {
            $gName = $good["name"];
            $gUrl = $good["url"];
            $amount = $basket[$good['ID']]['amount'];
            $price = $basket[$good['ID']]['price'];
            $gPrice = $amount * $price;
            $mail .= <<<EOT
<li>
<b><a href='$gUrl' target="_blank">
$gName</a></b>
<b> $price руб x $amount шт</b>
<b> = $gPrice руб.</b>";
</li>

EOT;
        }


        $mail .= <<<EOT
        <b>На сумму {$basket['total_price']} руб</b>
<br><br>

Данные покупателя:<br>

<table cellpadding="3">
  <tbody><tr><td>Адрес:</td><td>$address</td></tr>
  <tr><td>Способ доставки:</td><td>$delivery</td></tr>
  <tr><td>Способ оплаты:</td><td>$payment</td></tr>
  <tr><td>Ф.И.О.:</td><td>$name</td></tr>
  <tr><td>E-mail:</td><td>$email</a><br></td></tr>
  <tr><td>Телефон:</td><td>$phone</td></tr>
  <tr><td>Сообщение:</td><td>$message</td></tr>
</tbody></table>


<p style="color:#575757">Спасибо за обращение в Международный Центр Профилактики Онкологических Заболеваний.
<br>
</p><p style="color:#575757">Здоровья Вам и Вашим близким!

</p></div>
</div>
</div>
EOT;

        $to = "help1@neox.ru";
        if(isset($email)){
            $to .= ", $email";
        }
        $subject = 'Заявка с mcpoz.ru';
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

// Дополнительные заголовки
        $headers .= 'From: order@mcpoz.ru>' . "\r\n";

// Отправляем
        print $mail;
        mail($to, $subject, $mail, $headers);
        $tmp['content'] = mysql_real_escape_string($mail);
        $tmp['is_active'] = 1;
        $db->update("i_shop_structure_order", $id, $tmp);
    }

}
