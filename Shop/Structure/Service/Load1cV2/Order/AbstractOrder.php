<?php
namespace Shop\Structure\Service\Load1cV2\Order;

use Ideal\Core\Db;
use Ideal\Core\Config;

/**
 * Класс обеспечивающий работу с заказами в рамках интеграции с 1С
 */
class AbstractOrder
{
    public function generateExportXml()
    {
        $fileTemplate = array_slice(explode('\\', __FILE__), 0, -1);
        array_push($fileTemplate, 'export.xml');
        $fileTemplate = implode('\\', $fileTemplate);

        // Время начала формирования документа
        $docTime = time();

        $template = simplexml_load_file($fileTemplate);
        $template->addAttribute('ВерсияСхемы', '2.08');
        $template->addAttribute('ДатаФормирования', date('Y-m-d', $docTime));

        $db = Db::getInstance();
        $config = Config::getInstance();

        $i = $config->db['prefix'];
        $orderSql = "SELECT sho.id, sho.date_create, sho.name, sho.price,".
            " csu.name as buyerName, csu.last_name as buyerLastName, csu.ID as buyerID, csu.address as buyerAddress, csu.phone as buyerPhone, csu.email as buyerEmail,".
            " d.good_id_1c, d.offer_id_1c, d.count, d.sum,stg.currency, sto.name as full_name, stg.coefficient, sto.price as fe".
            " FROM {$i}shop_structure_order sho".
            " LEFT JOIN {$i}shop_structure_orderdetail d on d.order_id=sho.id".
            " LEFT JOIN {$i}catalogplus_structure_good stg on d.good_id_1c=stg.id_1c".
            " LEFT JOIN {$i}cabinet_structure_user csu on csu.ID = sho.user_id".
            " LEFT JOIN {$i}catalogplus_structure_offer sto on sto.offer_id=d.offer_id_1c".

            // В этих условиях задана проверка на обязательное наличие зарегистрированного пользователя
            // Если заказ сделал анонимный пользователь, то он, пока, не попадёт в 1С.
            " where sho.goods_id<>'' AND sho.export=1 AND csu.name IS NOT NULL LIMIT 0,200";

        $orderList = $db->select($orderSql);
        if (count($orderList) === 0) {
            die(print "success\n");
        }
        $items = array();
        foreach ($orderList as $element) {
            if (isset($items[$element['id']])) {
                $items[$element['id']]['goods'][] = array_slice($element, 10);
            } else {
                $items[$element['id']] = array_slice($element, 0, 10);
                $items[$element['id']]['goods'][] = array_slice($element, 10);
            }
        }
        unset($orderList);
        $upd = array();
        foreach ($items as $k => $item) {

            // Генерируем идентификатор документа
            // TODO Узнать, зачем генерировать идентификатор документа такого вида?
            /*$charid = strtolower(md5($item['id'] . $item['date_create']));
            $guid = substr($charid, 0, 8) . '-' . substr($charid, 8, 4) . '-' . substr($charid, 12, 4) . '-' . substr($charid, 16, 4) . '-' . substr($charid, 20, 12);*/

            $doc = $template->xpath("//КоммерческаяИнформация");
            /* @var $doc[0] \SimpleXMLElement */
            $doc = $doc[0]->addChild("Документ");

            $doc->addChild("Ид", $item['id']);
            $doc->addChild("Номер", $item['id']);
            $doc->addChild("Дата", date('Y-m-d', $item['date_create']));
            $doc->addChild("Время", date('H:m:s', $item['date_create']));
            $doc->addChild("ХозОперация", "Заказ товара");
            $doc->addChild("Роль", "Продавец");
            $doc->addChild("Валюта", "руб");
            $doc->addChild("Курс", 1);
            $doc->addChild("Сумма", number_format(floatval($item['price']) / 100, 2, '.', ''));

            $agents = $doc->addChild('Контрагенты');
            $agent = $agents->addChild('Контрагент');
            // TODO Узнать, зачем генерировать идентификатор пользователя такого вида?
            /*$charid = strtolower(md5('Физ лицо'));
            $guid = substr($charid, 0, 8) . '-' . substr($charid, 8, 4) . '-' . substr($charid, 12, 4) . '-' .
                substr($charid, 16, 4) . '-' . substr($charid, 20, 12);*/

            $agent->addChild("Ид", $item['buyerID']);
            $agent->addChild("Имя", $item['buyerName']);

            // Формируем наименование покапателя
            $buyerName = $item['buyerName'];
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

            $agent->addChild("Наименование", $buyerName);
            $agent->addChild("Роль", "Покупатель");
            $agent->addChild("ПолноеНаименование", $buyerName);

            $xmlGoods = $doc->addChild('Товары');
            foreach ($item['goods'] as $good) {
                $xmlGood = $xmlGoods->addChild('Товар');

                $xmlGood->addChild('Ид', $good['good_id_1c']);
                $xmlGood->addChild('Наименование', $good['full_name']);
                $xmlGood->addChild('ЦенаЗаЕдиницу', (int) $good['fe']/100);
                $xmlGood->addChild('Количество', $good['count']);
                $xmlGood->addChild('Сумма', $good['sum']/100);
                $xmlGood->addChild('Коэффициент', intval($good['coefficient']) ? intval($good['coefficient']) : 1);
                $props = $xmlGood->addChild('ЗначенияРеквизитов');
                $prop = $props->addChild('ЗначениеРеквизита');
                $prop->addChild("Наименование", "ВидНоменклатуры");
                $prop->addChild("Значение", "Товар");

                $prop = $props->addChild('ЗначениеРеквизита');
                $prop->addChild("Наименование", "ТипНоменклатуры");
                $prop->addChild("Значение", "Товар");

                if (!isset($currency) && isset($good['currency'])) {
                    $currency = $good['currency'];
                }
            }

            if (!isset($currency)) {
                $currency = "RUB";
            }
            $doc->addChild("Валюта", $currency);

            $upd[] = $item['id'];
        }

        foreach ($upd as $item) {
            $db->query("UPDATE {$i}shop_structure_order SET export=0 WHERE id={$item}");
        }
        return $template->asXML();
    }
}