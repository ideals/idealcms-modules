<?php

namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Order\Model as OrderModel;

class InfoModel
{
    public function execute(): string
    {
        $xml = simplexml_load_string(
            '<?xml version="1.0" encoding="utf-8"?>'
            . '<Справочник>'
            . '<Cтатусы></Cтатусы>'
            . '<ПлатежныеСистемы></ПлатежныеСистемы>'
            . '<СлужбыДоставки></СлужбыДоставки>'
            . '</Справочник>'
        );

        $order = new OrderModel();

        $doc = $xml->xpath('//Cтатусы')[0];
        foreach ($order->getStatuses() as $key => $status) {
            $element = $doc->addChild('Элемент');
            $element->addChild('Ид', $key);
            $element->addChild('Название', $status);
        }

        $doc = $xml->xpath('//ПлатежныеСистемы')[0];
        foreach ($order->getPaymentMethods() as $key => $method) {
            $element = $doc->addChild('Элемент');
            $element->addChild('Ид', $key);
            $element->addChild('Название', $method['name']);
            $element->addChild('ТипОплаты', $method['type']);
        }

        $doc = $xml->xpath('//СлужбыДоставки')[0];
        foreach ($order->getDeliveryMethods() as $key => $method) {
            $element = $doc->addChild('Элемент');
            $element->addChild('Ид', $key);
            $element->addChild('Название', $method);
        }

        return $xml->asXML();
    }
}
