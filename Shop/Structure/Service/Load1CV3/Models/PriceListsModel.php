<?php

namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Service\Load1CV3\ModelAbstract;

class PriceListsModel extends ModelAbstract
{

    public function init(): void
    {
        $this->setInfoText('Обработка типов цен каталога (priceLists)');
        $this->setSort(40);
    }

    public function startProcessing($filePath, $packageNum): array
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