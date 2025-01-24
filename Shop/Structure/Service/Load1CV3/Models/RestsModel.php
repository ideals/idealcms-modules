<?php

namespace Shop\Structure\Service\Load1CV3\Models;

use Ideal\Core\Config;
use Shop\Structure\Service\Load1CV3\Db\Rests\DbRests;
use Shop\Structure\Service\Load1CV3\Xml\Rests\XmlRests;
use Shop\Structure\Service\Load1CV3\Xml\Xml;
use Shop\Structure\Service\Load1CV3\ModelAbstract;

class RestsModel extends ModelAbstract
{
    protected XmlRests $xmlRests;

    public function init(): void
    {
        $this->setInfoText('Обработка остатков из пакета № %d');
        $this->setSort(100);

        // инициализируем модель остатков в XML - XmlRests
        $xml = new Xml($this->filename);
        $this->xmlRests = new XmlRests($xml);
        $this->isOnlyUpdate = $this->xmlRests->updateInfo();
    }

    /**
     * Запуск процесса обработки файлов rests_*.xml
     *
     * @param int $packageNum Номер пакета
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($packageNum): array
    {
        // Определяем пакет для отдачи правильного текста в ответе
        $this->answer['infoText'] = sprintf(
            $this->answer['infoText'],
            $packageNum
        );

        // инициализируем модель остатков в БД - DbRests
        $dbRests = new DbRests();

        // Устанавливаем связь БД и XML
        $rests = $this->parse($dbRests, $this->xmlRests);

        $dbRests->save($rests);

        return $this->answer();
    }

    /**
     * Возвращаем ответ пользователю о проделанной работе
     *
     * @return array ответ пользователю 'add'=>count(), 'update'=>count()
     */
    public function answer(): array
    {
        $this->answer['successText'] = sprintf(
            $this->answer['successText'],
            $this->answer['add'],
            $this->answer['update']
        );
        return $this->answer;
    }

    /**
     * Преобразование XML выгрузки в массив и сравнение с данными из БД
     *
     * @param DbRests $dbRests
     * @param XmlRests $xmlRests
     *
     * @return array двумерный массив с данными о ценах после сведения XML и БД
     */
    protected function parse($dbRests, $xmlRests)
    {
        $dbResult = $dbRests->parse();

        $xmlResult = $xmlRests->parse();

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

            $result[$k] = $val;
            $result[$k]['ID'] = $dbResult[$k]['ID'];
            $result[$k]['good_id'] = $dbResult[$k]['good_id'];
            $this->answer['update']++;
            $this->answer['tmpResult'][$whatIsThat]['update'][$key] = 1;
        }
        return $result;
    }
}
