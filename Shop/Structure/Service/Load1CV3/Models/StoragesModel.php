<?php

namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Service\Load1CV3\ModelAbstract;

class StoragesModel extends ModelAbstract
{

    public function init(): void
    {
        $this->setInfoText('Обработка складов каталога (storages)');
        $this->setSort(2);
    }

    public function startProcessing($filePath, $packageNum): array
    {
        $this->packageNum = $packageNum;
        $this->answer = [
            'infoText' => 'Данные о складах обработаны',
            'successText' => '',
            'add' => '',
            'update' => '',
        ];

        return $this->answer();
    }
}