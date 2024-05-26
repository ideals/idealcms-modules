<?php

namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Service\Load1CV3\Db\Storage\DbStorage;
use Shop\Structure\Service\Load1CV3\ModelAbstract;
use Shop\Structure\Service\Load1CV3\Xml\Storage\XmlStorage;
use Shop\Structure\Service\Load1CV3\Xml\Xml;

class StoragesModel extends ModelAbstract
{
    protected XmlStorage $xmlUnit;

    public function init(): void
    {
        $this->setInfoText('Обработка складов каталога (storages)');
        $this->setSort(30);

        // инициализируем модель остатков в XML - XmlUnit
        $xml = new Xml($this->filename);
        $this->xmlUnit = new XmlStorage($xml);
        $this->isOnlyUpdate = $this->xmlUnit->updateInfo();
    }

    /**
     * Запуск процесса обработки файлов storages_*.xml
     *
     * @param int $packageNum Номер пакета
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($packageNum): array
    {
        $this->packageNum = $packageNum;

        // инициализируем модель остатков в БД - DbRests
        $dbUnit = new DbStorage();

        // Устанавливаем связь БД и XML
        $storages = $this->parse($dbUnit, $this->xmlUnit);

        $dbUnit->save($storages);

        return $this->answer();
    }

    /**
     * Преобразование XML выгрузки в массив и сравнение с данными из БД
     *
     * @param DbStorage $dbUnits
     * @param XmlStorage $xmlUnits
     *
     * @return array двумерный массив с данными о ценах после сведения XML и БД
     */
    protected function parse($dbUnits, $xmlUnits)
    {
        // Забираем результаты единиц измерения из БД
        $dbResult = $dbUnits->parse();

        $xmlResult = $xmlUnits->parse();

        if (empty($xmlResult)) {
            $xmlResult = [];
        }

        return $this->diff($dbResult, $xmlResult);
    }

    /**
     * Сравнение результатов выгрузок. Если есть в xml и нет в БД - на добавление
     * Если есть в БД и есть в XML, но есть diff_assoc - добавляем поля для обновления.
     *
     * @param array $dbResult распарсенные данные из БД
     * @param array $xmlResult распарсенные данные из XML
     * @return array разница массивов на обновление и удаление
     */
    protected function diff(array $dbResult, array $xmlResult)
    {
        $result = [];
        foreach ($xmlResult as $k => $val) {
            if (!isset($dbResult[$k])) {
                $result[$k] = $val;
                $this->answer['add']++;
                $this->answer['tmpResult']['storage']['insert'][$k] = 1;
                continue;
            }

            $res = array_diff_assoc($val, $dbResult[$k]);
            if (count($res) > 0) {
                $result[$k] = $res;
                $result[$k]['ID'] = $dbResult[$k]['ID'];
                $this->answer['update']++;
                $this->answer['tmpResult']['storage']['update'][$k] = 1;
            }
        }

        return $result;
    }
}