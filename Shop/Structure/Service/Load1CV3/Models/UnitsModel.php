<?php
namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Service\Load1CV3\Db\Unit\DbUnit;
use Shop\Structure\Service\Load1CV3\ModelAbstract;
use Shop\Structure\Service\Load1CV3\Xml\Unit\XmlUnit;
use Shop\Structure\Service\Load1CV3\Xml\Xml;

class UnitsModel extends ModelAbstract
{
    protected XmlUnit $xmlUnit;

    public function init(): void
    {
        $this->setInfoText('Обработка единиц измерения (units)');
        $this->setSort(70);

        // инициализируем модель остатков в XML - XmlUnit
        $xml = new Xml($this->filename);
        $this->xmlUnit = new XmlUnit($xml);
        $this->isOnlyUpdate = $this->xmlUnit->updateInfo();
    }

    /**
     * Запуск процесса обработки файлов units_*.xml
     *
     * @param int $packageNum Номер пакета
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($packageNum): array
    {
        $this->packageNum = $packageNum;

        // инициализируем модель остатков в БД - DbRests
        $dbUnit = new DbUnit();

        // Устанавливаем связь БД и XML
        $rests = $this->parse($dbUnit, $this->xmlUnit);

        $dbUnit->save($rests);

        return $this->answer();
    }

    /**
     * Преобразование XML выгрузки в массив и сравнение с данными из БД
     *
     * @param DbUnit $dbUnits
     * @param XmlUnit $xmlUnits
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
            $goodOffer = explode('#', $k);
            if (substr_count($k, '#') === 1) {
                $whatIsThat = 'offers';
                $key = $goodOffer[1];
            } else {
                $whatIsThat = 'goods';
                $key = $goodOffer[0];
            }
            if (!isset($dbResult[$k])) {
                $result[$k] = $val;
                $this->answer['add']++;
                $this->answer['tmpResult'][$whatIsThat]['insert'][$key] = 1;
                continue;
            }

            $res = array_diff_assoc($val, $dbResult[$k]);
            if (count($res) > 0) {
                $result[$k] = $res;
                $result[$k]['ID'] = $dbResult[$k]['ID'];
                $result[$k]['good_id'] = $dbResult[$k]['good_id'];
                $this->answer['update']++;
                $this->answer['tmpResult'][$whatIsThat]['update'][$key] = 1;
            }
        }

        return $result;
    }
}
