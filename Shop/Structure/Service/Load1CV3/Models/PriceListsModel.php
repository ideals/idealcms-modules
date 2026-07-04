<?php

namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Service\Load1CV3\ModelAbstract;
use Shop\Structure\Service\Load1CV3\Xml\Xml;

class PriceListsModel extends ModelAbstract
{
    public function init(): void
    {
        $this->setInfoText('Обработка типов цен каталога (priceLists)');
        $this->setSort(10);
        // инициализируем модель типов цен в XML
        new Xml($this->filename);
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
