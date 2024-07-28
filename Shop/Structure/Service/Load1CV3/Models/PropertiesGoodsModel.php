<?php
namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Service\Load1CV3\ModelAbstract;

class PropertiesGoodsModel extends ModelAbstract
{
    public function init(): void
    {
        $this->setInfoText('Обработка свойств товаров (propertiesGoods)');
        $this->setSort(30);
    }

    /**
     * Запуск процесса обработки файлов propertiesGoods_*.xml
     *
     * @param int $packageNum Номер пакета
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($packageNum): array
    {
        $this->packageNum = $packageNum;

        // файл со свойствами товара не содержит значимой информации, поэтому ничего не делаем

        return $this->answer();
    }
}
