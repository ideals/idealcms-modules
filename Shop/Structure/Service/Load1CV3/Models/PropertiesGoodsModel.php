<?php
namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Service\Load1CV3\ModelAbstract;

class PropertiesGoodsModel extends ModelAbstract
{
    public function init(): void
    {
        $this->setInfoText('Обработка свойств товаров (propertiesGoods)');
        $this->setSort(50);
    }

    /**
     * Запуск процесса обработки файлов propertiesGoods_*.xml
     *
     * @param string $filePath Полный путь до обрабатываемого файла
     * @param int $packageNum Номер пакета
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($filePath, $packageNum): array
    {
        $this->filename = $filePath;
        $this->packageNum = $packageNum;

        // файл со свойствами товара не содержит значимой информации, поэтому ничего не делаем

        return $this->answer();
    }
}
