<?php
namespace Shop\Structure\Service\Load1CV3\Xml\Pricesold;

use Shop\Structure\Service\Load1CV3\Xml\Offer\XmlOffer;

class XmlPricesold extends XmlOffer
{
    /** @var string путь к категориям в XML */
    public $part = 'pricesold';

    /**
     * Получает идентификатор из данных Xml
     *
     * @param \SimpleXMLElement $item данные в xml формате
     * @return string Идентификатор определённый в конфиге
     */
    protected function getXmlId($item)
    {
        $xmlId = $item->xpath($this->ns . $this->configs['key']);
        return (string)$xmlId[0];
    }
}
