<?php

namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Service\Load1CV3\ModelAbstract;
use Shop\Structure\Service\Load1CV3\Xml\PriceLists\XmlPriceLists;
use Shop\Structure\Service\Load1CV3\Xml\Xml;

class PriceListsModel extends ModelAbstract
{
    private XmlPriceLists $xmlPriceLists;

    public function init(): void
    {
        $this->setInfoText('Обработка типов цен каталога (priceLists)');
        $this->setSort(10);

        // инициализируем модель типов цен в XML
        $xml = new Xml($this->filename);
        $this->xmlPriceLists = new XmlPriceLists($xml);
        $this->isOnlyUpdate = $this->xmlPriceLists->updateInfo();
    }

    public function startProcessing($packageNum): array
    {
        $this->packageNum = $packageNum;
        $this->answer = [
            'infoText' => 'Данные о типах цен обработаны',
            'successText' => '',
            'add' => '',
            'update' => '',
        ];

        return $this->answer();
    }
}
