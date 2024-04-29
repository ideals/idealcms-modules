<?php

namespace Shop\Structure\Service\Load1CV3\Models;

use Ideal\Core\Db;
use Ideal\Core\Config;

class QueryModel
{
    public function generateExportXml()
    {
        // Время начала формирования документа
        $docTime = time();

        $xml = simplexml_load_string(
            '<?xml version="1.0" encoding="utf-8"?><КоммерческаяИнформация></КоммерческаяИнформация>'
        );
        $doc = $xml->xpath('//КоммерческаяИнформация');
        $doc[0]->addAttribute('ВерсияСхемы', '3.1');
        $doc[0]->addAttribute('ДатаФормирования', date('Y-m-d', $docTime));

        $db = Db::getInstance();
        $config = Config::getInstance();

        $i = $config->db['prefix'];

        $sql = <<<ORDERSQL
            SELECT 
            sso.ID,
            sso.status,
            sso.date_create,
            sso.price,
            sso.order_comment,
            sso.delivery_method,
            sso.payment_method,
            sso.delivery_fio,
            sso.delivery_phone,
            sso.delivery_email,
            sso.delivery_country,
            sso.delivery_city,
            sso.delivery_address,
            sso.orderId1c,
            csu.login as buyerLogin, 
            csu.name as buyerName, 
            csu.last_name as buyerLastName, 
            csu.ID as buyerId, 
            csu.id_1c as buyerId1c, 
            csu.phone as buyerPhone, 
            csu.email as buyerEmail
          FROM {$i}shop_structure_order sso
          LEFT JOIN {$i}cabinet_structure_user csu on csu.ID = sso.user_id
          WHERE sso.export=1 AND csu.name IS NOT NULL LIMIT 0,200
        ORDERSQL;

        $orders = $db->select($sql);
        if (count($orders) === 0) {
            return '';
        }

        $exportOrdersIds = [];
        $exportPaymentIds = []; // todo вынести в отдельный код
        foreach ($orders as $order) {
            $doc = $xml->xpath('//КоммерческаяИнформация');
            /* @var $doc [0] \SimpleXMLElement */
            $doc = $doc[0]->addChild('Контейнер');

            $this->createOrderDocument($doc, $order);

            $exportPaymentIds[] = $this->addPaymentDocuments($doc, $order);

//            $this->addShipmentDocuments($doc, $order);

            $exportOrdersIds[] = $order['ID'];
        }

        $exportPaymentIds = array_merge(...$exportPaymentIds);

        foreach ($exportOrdersIds as $orderId) {
            $db->query("UPDATE {$i}shop_structure_order SET export=0 WHERE id=$orderId");
        }

        foreach ($exportPaymentIds as $paymentId) {
            $db->query("UPDATE {$i}shop_structure_orderpay SET export=0 WHERE id=$paymentId");
        }

        return $xml->asXML();
    }

    private function createOrderDocument(\SimpleXMLElement $doc, array $order): void
    {
        $doc = $doc->addChild('Документ');

        $orderId1c = empty($order['orderId1c']) ? $order['ID'] : $order['orderId1c'];
        $doc->addChild('Ид', $orderId1c);
        $doc->addChild('Номер', $orderId1c);
        $doc->addChild('Дата', date('Y-m-d', $order['date_create']));
        $doc->addChild('Время', date('H:m:s', $order['date_create']));
        $doc->addChild('ХозОперация', 'Заказ товара');
        $doc->addChild('Роль', 'Продавец');
        $doc->addChild('Валюта', 643); // todo задать идентификатор валюты RUB
        $doc->addChild('Курс', 1);
        $doc->addChild('Сумма', number_format((float) $order['price'] / 100, 2, '.', ''));

        /** @var \SimpleXMLElement $detailsValues */
        $detailsValues = $doc->addChild('ЗначенияРеквизитов');

        // Добавляем в документ информацию о доставке
        if ($deliveryInfo = $this->getOrderDeliveryAddress($order)) {
            /** @var \SimpleXMLElement $detailValue */
            $detailValue = $detailsValues->addChild('ЗначениеРеквизита');
            $detailValue->addChild('Наименование', 'Адрес доставки');
            $detailValue->addChild('Значение', $deliveryInfo);
        }

        // Добавляем статус заказа
        $detailValue = $detailsValues->addChild('ЗначениеРеквизита');
        $detailValue->addChild('Наименование', 'Статуса заказа ИД');
        $detailValue->addChild('Значение', $this->getOrderStatus($order['status']));

        // Добавляем способ доставки
        $detailValue = $detailsValues->addChild('ЗначениеРеквизита');
        $detailValue->addChild('Наименование', 'Метод доставки ИД');
        $detailValue->addChild('Значение', $this->getDeliveryMethod($order['delivery_method']));

        $this->generateBuyer($doc, $order);

        $this->generateGoodsList($doc, $order);

        // Отправляем комментарий к заказу в 1С
        $orderComment = $order['order_comment'] ?? '';
        if (!empty($order['payment_method'])) {
            $orderComment .= "\nСпособ оплаты: " . $order['payment_method'];
        }
        $doc->addChild('Комментарий', $orderComment);
    }

    protected function getOrderDeliveryAddress(array $order): string
    {
        $deliveryInfo = $order['delivery_address'] ?? '';

        if (!empty($order['delivery_country']) && !empty($order['delivery_city'])) {
            $deliveryInfo = $deliveryInfo ? ', ' . $deliveryInfo : '';
            $deliveryInfo = $order['delivery_country'] . ', г. ' . $order['delivery_city'] . $deliveryInfo;
        }

        return $deliveryInfo;
    }

    private function generateBuyer(\SimpleXMLElement $doc, array $order): void
    {
        /** @var \SimpleXMLElement $agents */
        $agents = $doc->addChild('Контрагенты');
        /** @var \SimpleXMLElement $agent */
        $agent = $agents->addChild('Контрагент');

        $agent->addChild('Ид', $order['buyerId1c'] ?: $order['buyerId']);
        $agent->addChild('Роль', 'Покупатель');

        // Формируем наименование покупателя
        $fullName = trim(($order['buyerLastName'] ?? '') . ' ' . ($order['buyerName'] ?? ''));
        $agent->addChild('Наименование', $order['delivery_fio'] ?: $fullName);
        $agent->addChild('ПолноеНаименование', $order['delivery_fio'] ?: $fullName);

        /** @var \SimpleXMLElement $contacts */
        $contacts = $agent->addChild('Контакты');

        // Если у заказчика указан телефон, то передаём его в 1С
        $phone = $order['delivery_phone'] ?: $order['buyerPhone'];
        if (!empty($phone)) {
            // Подготавливаем поле телефон для отправки в 1С.
            // При повторном обмене на сайт могут быть записаны данные в "неправильном" формате.
            $phone = preg_replace('/\D/', '', $phone);

            // Убираем код страны из телефонного номера (при его наличии)
            if (mb_strlen($phone) === 10) {
                $phone= '7' . $phone;
            }

            /** @var \SimpleXMLElement $buyerPhoneContact */
            $buyerPhoneContact = $contacts->addChild('Контакт');
            $buyerPhoneContact->addChild('Тип', 'Телефон рабочий');
            $buyerPhoneContact->addChild('Значение', $phone);
        }

        // Если у заказчика указан e-mail, то передаём его в 1С
        $email = $order['delivery_email'] ?: $order['buyerEmail'];
        if (!empty($email)) {
            /** @var \SimpleXMLElement $buyerEmailContact */
            $buyerEmailContact = $contacts->addChild('Контакт');
            $buyerEmailContact->addChild('Тип', 'Электронная почта');
            $buyerEmailContact->addChild('Значение', $email);
        }
    }

    private function generateGoodsList(\SimpleXMLElement $doc, array $order): void
    {
        $db = Db::getInstance();
        $goods = $db->select(
            'SELECT * FROM i_shop_structure_orderdetail WHERE order_id=' . $order['ID']
        );

        /** @var \SimpleXMLElement $xmlGoods */
        $xmlGoods = $doc->addChild('Товары');
        foreach ($goods as $good) {
            /** @var \SimpleXMLElement $xmlGood */
            $xmlGood = $xmlGoods->addChild('Товар');

            // Если у заказанного товара есть предложение, то его идентификатор участвует в формировании идентификатора
            // товара в документе
            $goodId1c = $good['good_id_1c'];
            if (isset($good['offer_id_1c']) && $good['good_id_1c'] !== $good['offer_id_1c']) {
                $goodId1c .= '#' . $good['offer_id_1c'];
            }

            $xmlGood->addChild('Ид', $goodId1c);
            $xmlGood->addChild('Наименование', $good['name']);

            // Рассчитываем сумму без скидок/наценок
            // todo проверить, как идёт расчёт скидок при заказе
//            $price = (float)$good['fe'] / 100 + (float)$good['discount'] / 100;
            $xmlGood->addChild('Цена', number_format($good['price'] / 100, 2, '.', ''));
            $xmlGood->addChild('Количество', $good['count']);
            $xmlGood->addChild('Сумма', number_format($good['sum'] / 100, 2, '.', ''));
            $xmlGood->addChild('Коэффициент', (int) $good['coefficient'] ?: 1);
            /** @var \SimpleXMLElement $props */
            $props = $xmlGood->addChild('ЗначенияРеквизитов');
            /** @var \SimpleXMLElement $prop */
            $prop = $props->addChild('ЗначениеРеквизита');
            $prop->addChild('Наименование', 'ТипНоменклатуры');
            $prop->addChild('Значение', 'Товар');

            // Добавляем информацию о скидке
            if ((int) $good['discount'] !== 0) {
                /** @var \SimpleXMLElement $discounts */
                $discounts = $xmlGood->addChild('Скидки');
                /** @var \SimpleXMLElement $discount */
                $discount = $discounts->addChild('Скидка');
                $discount->addChild('Наименование', 'Скидка');
                $discount->addChild('Сумма', number_format((float)$good['discount'] / 100, 2, '.', ''));

                // Высчитываем процент скидки
                $percentDiscount = $good['discount'] / (($good['discount'] + $good['sum']) / 100);
                $discount->addChild('Процент', number_format($percentDiscount, 2, '.', ''));
                $discount->addChild('УчтеноВСумме', 'true');
            }
        }
    }

    private function getDeliveryMethod(string $deliveryMethod): int
    {
        $oldMethods = [
            'Самовывоз' => 1,
            'До клиента' => 2,
            'Силами перевозчика' => 3,
        ];

        $newMethods = [
            'Самовывоз' => 1,
            'Доставка курьером по Москве (в пределах МКАД)' => 2,
            'Доставка транспортной компанией СДЭК' => 3,
            'Доставка Почтой России' => 4,
        ];

        return $oldMethods[$deliveryMethod] ?? $newMethods[$deliveryMethod] ?? 1;
    }

    private function getOrderStatus(string $status): string
    {
        $oldStatuses = [
            'На согласовании' => 'P',
            'К выполнению / В резерве' => 'A',
            'Закрыт' => 'F',
        ];

        $newStatuses = [
            'В обработке' => 'P',
            'Зарезервирован. Ожидает 100% оплаты' => 'A',
            'Изготовление на заказ' => 'W',
            'Отгружен. Ожидает 100% оплаты' => 'X',
            'Оплачен. Ожидает отгрузки' => 'S',
            'Выполнен' => 'F',
        ];

        return $oldStatuses[$status] ?? $newStatuses[$status] ?? 'P';
    }

    private function addPaymentDocuments($doc, $order): array
    {
        // todo вынести из основного кода, т.к. платежи должны обрабатываться в каждом проекте отдельно
        $db = Db::getInstance();
        $pays = $db->select('SELECT * FROM i_shop_structure_orderpay WHERE is_export=1 AND order_id=' . $order['ID']);

        $ids = [];
        foreach ($pays as $pay) {
            $ids[] = $pay['ID'];
            $child = $doc->addChild('Документ');
            $child->addChild('Ид', $pay['id_1c']);
//            $child->addChild('Номер1С', $orderId1c);
            $child->addChild('Дата', date('Y-m-d', strtotime($pay['date'])));
            $child->addChild('Время', date('H:m:s', strtotime($pay['date'])));
            $child->addChild('ХозОперация', $this->getPayMethodName((int) $pay['payment_method_id'])['operation']);
            $this->generateBuyer($child, $order);
            $child->addChild('Валюта', 643);
            $child->addChild('Курс', 1);
            $child->addChild('Сумма', number_format((float) $pay['price'] / 100, 2, '.', ''));
            $child->addChild('Основание', empty($order['orderId1c']) ? $order['ID'] : $order['orderId1c']);
            $child->addChild('Роль', 'Продавец');

            /** @var \SimpleXMLElement $detailsValues */
            $detailsValues = $child->addChild('ЗначенияРеквизитов');
            /** @var \SimpleXMLElement $detailValue */
            $detailValue = $detailsValues->addChild('ЗначениеРеквизита');
            $detailValue->addChild('Наименование', 'Оплачен');
            $detailValue->addChild('Значение', 'true'); // с сайта выгружаются только совершённые оплаты
        }

        return $ids;
    }

    private function getPayMethodName(int $id): array
    {
        $methods = [
            1 => [
                'name' => 'Оплата по реквизитам',
                'type' => 'Безналичная оплата',
                'operation' => 'Выплата безналичных денег',
            ],
            2 => [
                'name' => 'Оплата картой на сайте',
                'type' => 'Безналичная оплата',
                'operation' => 'Выплата безналичных денег',
            ],
            3 => [
                'name' => 'Оплата по СБП на сайте',
                'type' => 'Безналичная оплата',
                'operation' => 'Выплата безналичных денег',
            ],
            4 => [
                'name' => 'Наличный расчёт 1',
                'type' => 'Наличная оплата',
                'operation' => 'Выплата наличных денег',
            ],
            5 => [
                'name' => 'Наличный расчёт 2',
                'type' => 'Наличная оплата',
                'operation' => 'Выплата наличных денег',
            ],
            6 => [
                'name' => 'Наличный расчёт 3',
                'type' => 'Наличная оплата',
                'operation' => 'Выплата наличных денег',
            ],
            7 => [
                'name' => 'Наличный расчёт 4',
                'type' => 'Наличная оплата',
                'operation' => 'Выплата наличных денег',
            ],
            8 => [
                'name' => 'Наличный расчёт 5',
                'type' => 'Наличная оплата',
                'operation' => 'Выплата наличных денег',
            ],
            9 => [
                'name' => 'Наличный расчёт 6',
                'type' => 'Наличная оплата',
                'operation' => 'Выплата наличных денег',
            ],
            10 => [
                'name' => 'Наличный расчёт 7',
                'type' => 'Наличная оплата',
                'operation' => 'Выплата наличных денег',
            ],
            11 => [
                'name' => 'Наличный расчёт 8',
                'type' => 'Наличная оплата',
                'operation' => 'Выплата наличных денег',
            ],
            12 => [
                'name' => 'Наличный расчёт 9',
                'type' => 'Наличная оплата',
                'operation' => 'Выплата наличных денег',
            ],
            13 => [
                'name' => 'Наличный расчёт 10',
                'type' => 'Наличная оплата',
                'operation' => 'Выплата наличных денег',
            ],
            14 => [
                'name' => 'Оплата картой в магазине',
                'type' => 'Эквайринговая оплата',
                'operation' => 'Эквайринговая операция',
            ],
            15 => [
                'name' => 'Оплата по СБП в магазине',
                'type' => 'Эквайринговая оплата',
                'operation' => 'Эквайринговая операция',
            ],
        ];

        return $methods[$id];
    }
}
