<?php
namespace Shop\Structure\Service\Load1CV3\Models;

use Ideal\Core\Db;
use Ideal\Core\Config;

class OrderModel
{
    public function generateExportXml()
    {
        $fileTemplate = DOCUMENT_ROOT . '/don/Mods/Shop/Structure/Service/Load1CV3/export.xml';
//        return file_get_contents($fileTemplate);

        // Время начала формирования документа
        $docTime = time();

        $template = simplexml_load_string(file_get_contents($fileTemplate));
        $doc = $template->xpath("//КоммерческаяИнформация");
        $doc[0]->addAttribute('ВерсияСхемы', '2.08');
        $doc[0]->addAttribute('ДатаФормирования', date('Y-m-d', $docTime));


        $db = Db::getInstance();
        $config = Config::getInstance();

        $i = $config->db['prefix'];

        // В этих условиях задана проверка на обязательное наличие зарегистрированного пользователя
        // Если заказ сделал анонимный пользователь, то он, пока, не попадёт в 1С.
        $orderSql = <<<ORDERSQL
          SELECT 
            sso.id,
            sso.date_create,
            sso.name,
            sso.price,
            sso.order_comment,
            sso.delivery_method,
            sso.payment_method,
            sso.delivery_phone,
            sso.delivery_country,
            sso.delivery_city,
            sso.delivery_address,
            sso.orderId1c,
            csu.login as buyerLogin, 
            csu.name as buyerName, 
            csu.last_name as buyerLastName, 
            csu.ID as buyerID, 
            csu.phone as buyerPhone, 
            csu.email as buyerEmail,
            ssod.good_id_1c,
            ssod.offer_id_1c,
            ssod.count,
            ssod.sum,
            ssod.currency,
            ssod.name as full_name,
            ssod.coefficient,
            ssod.price as fe,
            ssod.discount as discount
          FROM {$i}shop_structure_order sso
          LEFT JOIN {$i}shop_structure_orderdetail ssod on ssod.order_id=sso.id
          LEFT JOIN {$i}cabinet_structure_user csu on csu.ID = sso.user_id
          WHERE sso.goods_id != '' AND sso.export=1 AND csu.name IS NOT NULL LIMIT 0,200
ORDERSQL;

        $orderList = $db->select($orderSql);
        if (count($orderList) === 0) {
            return '';
        }
        $items = array();
        foreach ($orderList as $element) {
            if (isset($items[$element['id']])) {
                $items[$element['id']]['goods'][] = array_slice($element, 18);
            } else {
                $items[$element['id']] = array_slice($element, 0, 18);
                $items[$element['id']]['goods'][] = array_slice($element, 18);
            }
        }
        unset($orderList);
        $upd = array();
        foreach ($items as $k => $item) {
            $doc = $template->xpath("//КоммерческаяИнформация");
            /* @var $doc[0] \SimpleXMLElement */
            $doc = $doc[0]->addChild("Документ");

            !empty($item['orderId1c']) ? $orderId1c = $item['orderId1c'] : $orderId1c = $item['id'];

            $doc->addChild("Ид", $orderId1c);
            $doc->addChild("Номер", $orderId1c);
            $doc->addChild("Дата", date('Y-m-d', $item['date_create']));
            $doc->addChild("Время", date('H:m:s', $item['date_create']));
            $doc->addChild("ХозОперация", "Заказ товара");
            $doc->addChild("Роль", "Продавец");
            $doc->addChild("Валюта", "руб");
            $doc->addChild("Курс", 1);
            $doc->addChild("Сумма", number_format((float)$item['price'] / 100, 2, '.', ''));

            $detailsValues = $doc->addChild("ЗначенияРеквизитов");
            $detailValue = $detailsValues->addChild("ЗначениеРеквизита");
            $detailValue->addChild("Наименование", "Способ доставки");
            if (!empty($item['delivery_method']) && $item['delivery_method'] != 'Самовывоз') {
                $detailValue->addChild("Значение", 'До клиента');
            } else {
                $detailValue->addChild("Значение", 'Самовывоз');
            }

            $deliveryInfo = '';
            // Добавляем в документ информацию о доставке
            if (!empty($item['address'])) {
                $deliveryInfo = $item['address'];
            }
            if (!empty($item['delivery_country']) && !empty($item['delivery_city'])) {
                if ($deliveryInfo) {
                    $deliveryInfo = ', ' . $deliveryInfo;
                }
                $deliveryInfo = "{$item['delivery_country']}, г. {$item['delivery_city']}{$deliveryInfo}";
            }

            if ($deliveryInfo) {
                $detailValue = $detailsValues->addChild("ЗначениеРеквизита");
                $detailValue->addChild("Наименование", "Адрес доставки");
                $detailValue->addChild("Значение", $deliveryInfo);

                $detailValue = $detailsValues->addChild("ЗначениеРеквизита");
                $detailValue->addChild("Наименование", "Адрес получателя");
                $detailValue->addChild("Значение", $deliveryInfo);
            }

            // Формируем ФИО получателя
            $deliveriFIO = '';
            if (!empty($item['delivery_last_name'])) {
                $deliveriFIO .= $item['delivery_last_name'];
            }

            if (!empty($deliveriFIO)) {
                $detailValue = $detailsValues->addChild("ЗначениеРеквизита");
                $detailValue->addChild("Наименование", "ФИО получателя");
                $detailValue->addChild("Значение", $deliveriFIO);
            }

            // Передаём Телефон получателя
            if (!empty($item['delivery_phone'])) {
                $detailValue = $detailsValues->addChild("ЗначениеРеквизита");
                $detailValue->addChild("Наименование", "Телефон получателя");
                $detailValue->addChild("Значение", $item['delivery_phone']);
            }

            $agents = $doc->addChild('Контрагенты');
            $agent = $agents->addChild('Контрагент');

            $buyerName = '';
            if (!empty($item['buyerName'])) {
                $buyerName = $item['buyerName'];
            }

            $agent->addChild("Ид", $item['buyerID']);
            $agent->addChild("Имя", $item['buyerName']);

            // Формируем наименование покапателя

            if (!empty($item['buyerLastName'])) {
                $buyerName .= ' ' . $item['buyerLastName'];
                $agent->addChild("Фамилия", $item['buyerLastName']);
            }

            $regAddress = $agent->addChild("АдресРегистрации");
            // Если у заказчика указан адрес, то передаём его в 1С
            if (!empty($item['buyerAddress'])) {
                $regAddress->addChild('Представление', $item['buyerAddress']);
            }

            $contacts = $agent->addChild('Контакты');

            // Если у заказчика указан телефон, то передаём его в 1С
            if (!empty($item['buyerPhone'])) {
                // Подготавливаем поле телефон для отправки в 1С.
                // При повторном обмене на сайт могут быть записаны данные в "неправильном" формате.
                $item['buyerPhone'] = preg_replace('/\D/', '', $item['buyerPhone']);

                // Убираем код страны из телефонного номера (при его наличии)
                if (iconv_strlen($item['buyerPhone']) === 10) {
                    $item['buyerPhone'] = '7' . $item['buyerPhone'];
                }

                $buyerPhoneContact = $contacts->addChild('Контакт');
                $buyerPhoneContact->addChild('Тип', 'Телефон рабочий');
                $buyerPhoneContact->addChild('Значение', $item['buyerPhone']);
            }

            // Если у заказчика указан e-mail, то передаём его в 1С
            if (!empty($item['buyerEmail'])) {
                $buyerEmailContact = $contacts->addChild('Контакт');
                $buyerEmailContact->addChild('Тип', 'Электронная почта');
                $buyerEmailContact->addChild('Значение', $item['buyerEmail']);
            }

            // Если указано имя или фамилия, то в "Рабочее наименование" отдаём эти данные
            !empty($buyerName) ? $workName = $buyerName : $workName = $item['buyerLogin'];


            $agent->addChild("Наименование", $workName);
            $agent->addChild("Роль", "Покупатель");
            $agent->addChild("ПолноеНаименование", $buyerName);

            // Отправляем комментарий к заказу в 1С
            $orderComment = '';
            if (!empty($item['order_comment'])) {
                $orderComment .= $item['order_comment'];
            }
            if (!empty($item['payment_method'])) {
                $orderComment .= "\nСпособ оплаты: {$item['payment_method']}";
            }
            $doc->addChild('Комментарий', $orderComment);

            $xmlGoods = $doc->addChild('Товары');
            foreach ($item['goods'] as $good) {
                $xmlGood = $xmlGoods->addChild('Товар');

                // Если у заказанного товара есть оффер, то его идентификатор участвует в формировании идентификатора
                // товара в документе
                $goodId1c = $good['good_id_1c'];
                if (isset($good['offer_id_1c']) && $good['good_id_1c'] != $good['offer_id_1c']) {
                    $goodId1c .= '#' . $good['offer_id_1c'];
                }

                $xmlGood->addChild('Ид', $goodId1c);
                $xmlGood->addChild('Наименование', $good['full_name']);
                $itemOne = $xmlGood->addChild('Единица');
                $itemOne->addChild('Ид', '796');
                $itemOne->addChild('НаименованиеКраткое', 'шт');
                $itemOne->addChild('Код', '796');
                $itemOne->addChild('НаименованиеПолное', 'Штука');

                // Рассчитываем сумму без скидок/наценок
                $price = (float)$good['fe'] / 100 + (float)$good['discount'] / 100;
                $xmlGood->addChild('Цена', number_format($price, 2, '.', ''));
                $xmlGood->addChild('Количество', $good['count']);
                $xmlGood->addChild('Сумма', number_format((float)$good['sum'] / 100, 2, '.', ''));
                $xmlGood->addChild('Коэффициент', (int)$good['coefficient'] ? (int)$good['coefficient'] : 1);
                $props = $xmlGood->addChild('ЗначенияРеквизитов');
                $prop = $props->addChild('ЗначениеРеквизита');
                $prop->addChild('Наименование', 'ТипНоменклатуры');
                $prop->addChild('Значение', 'Товар');

                // Добавляем информацию о скидке
                if ($good['discount'] != 0) {
                    $discounts = $xmlGood->addChild('Скидки');
                    $discount = $discounts->addChild('Скидка');
                    $discount->addChild('Наименование', 'Скидка');
                    $discount->addChild('Сумма', number_format((float)$good['discount'] / 100, 2, '.', ''));

                    // Высчитываем процент скидки
                    $percentDiscount = $good['discount'] / ($good['sum'] / 100);
                    $discount->addChild('Процент', number_format((float)$percentDiscount, 2, '.', ''));
                    $discount->addChild('УчтеноВСумме', 'true');
                }
            }
            $doc->addChild("Валюта", 'RUB');

            $upd[] = $item['id'];
        }

        foreach ($upd as $item) {
            $db->query("UPDATE {$i}shop_structure_order SET export=0 WHERE id={$item}");
        }
        return $template->asXML();
    }
}