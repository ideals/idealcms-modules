<?php
namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Service\Load1CV3\Db\Offer\DbOffer;
use Shop\Structure\Service\Load1CV3\Xml\Directory\XmlDirectory;
use Shop\Structure\Service\Load1CV3\Db\Directory\DbDirectory;
use Shop\Structure\Service\Load1CV3\Xml\Offer\XmlOffer;
use Shop\Structure\Service\Load1CV3\Xml\Xml;

class OffersModel
{
    /** @var array Массив содержащий структурированный ответ по факту обработки файла */
    protected $answer = array(
        'infoText' => 'Обработка офферов из пакета № %d',
        'successText' => 'Добавлено: %d<br />Обновлено: %d',
        'add'   => 0,
        'update'=> 0,
    );

    /**
     * Запуск процесса обработки файлов offers_*.xml
     *
     * @param string $filePath полный путь до обрабатываемого файла
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($filePath)
    {
        // Файл offers_*.xml может быть двух типов.
        // Корневой содержит информацию о свойствах товаров.
        // Пакетный, содержит информацию о торговых предложениях.
        // Определяем тип файла и в зваисимости от этого запускаем нужну оюработку.
        $xml = new Xml($filePath);
        $xmlDirectory = new XmlDirectory($xml);
        if ($xmlDirectory->validate()) {
            $this->directory($filePath);
        } else {
            $this->offers($filePath);
        }
        return $this->answer();
    }

    /**
     * Возвращаем ответ пользователю о проделанной работе
     *
     * @return array ответ пользователю 'add'=>count(), 'update'=>count()
     */
    public function answer()
    {
        $this->answer['successText'] = sprintf(
            $this->answer['successText'],
            $this->answer['add'],
            $this->answer['update']
        );
        return $this->answer;
    }

    /**
     * Обновление справочников из выгрузки
     *
     * @param string $filePath путь до одного из основных файлов выгрузки
     */
    protected function directory($filePath)
    {
        $xml = new Xml($filePath);

        // Инициализируем модель справочников в БД - DbDirectory
        $dbDirectory = new DbDirectory();

        // Инициализируем модель справочников в XML - XmlDirectory
        $xmlDirectory = new XmlDirectory($xml);

        // Устанавливаем связь БД и XML, производим сравнение
        $directories = $this->directoryParse($dbDirectory, $xmlDirectory);

        // Сохраняем результаты
        $dbDirectory->save($directories);

        $this->answer['infoText'] = 'Обработка справочников';
    }

    /**
     * Парсинг данных DbGood и XmlGood, и их сравнение
     *
     * @param DbDirectory $dbDirectory
     * @param XmlDirectory $xmlDirectory
     *
     * @return array разница, которую передаем объекту DbGood для сохранения
     */
    protected function directoryParse($dbDirectory, $xmlDirectory)
    {
        // Забираем справочники из БД
        $dbResult = $dbDirectory->parse();

        // Забираем справочники из xml 1m
        $xmlResult = $xmlDirectory->parse();

        return $this->directoryDiff($dbResult, $xmlResult);
    }

    /**
     * Сравнение результатов выгрузок. Если есть в xml и нет в БД - на добавление
     * Если есть в БД и есть в XML, но есть diff_assoc - добавляем поля для обновления.
     *
     * @param array $dbResult распарсенные данные из БД
     * @param array $xmlResult распарсенные данные из XML
     * @return array разница массивов на обновление и удаление
     */
    protected function directoryDiff(array $dbResult, array $xmlResult)
    {
        $result = array();
        foreach ($xmlResult as $k => $val) {
            if (!isset($dbResult[$k])) {
                $result[$k] = $val;
                $this->answer['add']++;
                $this->answer['tmpResult']['directory']['insert'][$val['dir_id_1c']] = 1;
                continue;
            }

            $res = array_diff_assoc($val, $dbResult[$k]);
            if (count($res) > 0) {
                $result[$k] = array_merge($dbResult[$k], $res);
                $this->answer['update']++;
                $this->answer['tmpResult']['directory']['update'][$val['dir_id_1c']] = 1;
            }
        }
        return $result;
    }

    /**
     * Обработка данных о предложениях
     *
     * @param string $filePath путь до одного из файлов выгрузки
     */
    protected function offers($filePath)
    {
        // Определяем пакет для отдачи правильного текста в ответе
        $dir = pathinfo($filePath, PATHINFO_DIRNAME);
        $dirParts = explode(DIRECTORY_SEPARATOR, $dir);
        $packageNum = end($dirParts);
        $this->answer['infoText'] = sprintf(
            $this->answer['infoText'],
            $packageNum
        );

        // получение xml с данными о предложениях
        $xml = new Xml($filePath);

        // инициализируем модель предложений в БД - DbOffer
        $dbOffers = new DbOffer();

        // инициализируем модель предложений в XML - XmlOffer
        $xmlOffers = new XmlOffer($xml);

        // Устанавливаем связь БД и XML
        $offers = $this->offersParse($dbOffers, $xmlOffers);

        $dbOffers->save($offers);
    }

    /**
     * Парсинг данных DbGood и XmlGood, и их сравнение
     *
     * @param DbOffer $dbOffers
     * @param XmlOffer $xmlOffers
     *
     * @return array разница, которую передаем объекту DbGood для сохранения
     */
    public function offersParse($dbOffers, $xmlOffers)
    {
        // Забираем офферы из БД
        $dbResult = $dbOffers->parse();

        // Забираем офферы из xml
        $xmlResult = $xmlOffers->parse();

        if (empty($xmlResult)) {
            $xmlResult = array();
        }

        return $this->offersDiff($dbResult, $xmlResult);
    }

    /**
     * Сравнение результатов выгрузок. Если есть в xml и нет в БД - на добавление
     * Если есть в БД и есть в XML, но есть diff_assoc - добавляем поля для обновления.
     *
     * @param array $dbResult распарсенные данные из БД
     * @param array $xmlResult распарсенные данные из XML
     * @return array разница массивов на обновление и удаление
     */
    protected function offersDiff(array $dbResult, array $xmlResult)
    {
        $result = array();
        foreach ($xmlResult as $k => $val) {
            if (!isset($dbResult[$k])) {
                $result[$k] = $val;
                $this->answer['add']++;
                $this->answer['tmpResult']['offers']['insert'][$k] = 1;
                continue;
            }

            $res = array_diff_assoc($val, $dbResult[$k]);
            if (count($res) > 0) {
                $result[$k] = $res;
                $result[$k]['ID'] = $dbResult[$k]['ID'];
                $result[$k]['good_id'] = $dbResult[$k]['good_id'];
                $this->answer['update']++;
                $this->answer['tmpResult']['offers']['update'][$k] = 1;
            }
        }
        return $result;
    }
}
