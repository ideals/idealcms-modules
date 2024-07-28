<?php
namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Service\Load1CV3\Db\Directory\DbDirectory;
use Shop\Structure\Service\Load1CV3\ModelAbstract;
use Shop\Structure\Service\Load1CV3\Xml\Directory\XmlDirectory;
use Shop\Structure\Service\Load1CV3\Xml\Xml;

class PropertiesOffersModel extends ModelAbstract
{
    protected XmlDirectory $xmlDirectory;

    public function init(): void
    {
        $this->setInfoText('Обработка свойств предложений (propertiesOffers)');
        $this->setSort(40);

        // Инициализируем модель справочников в XML - XmlDirectory
        $xml = new Xml($this->filename);
        $this->xmlDirectory = new XmlDirectory($xml);
        $this->isOnlyUpdate = $this->xmlDirectory->updateInfo();
    }

    /**
     * Запуск процесса обработки файлов propertiesOffers_*.xml
     *
     * @param int $packageNum Номер пакета
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($packageNum): array
    {
        $this->packageNum = $packageNum;

        // Инициализируем модель справочников в БД - DbDirectory
        $dbDirectory = new DbDirectory();

        // Устанавливаем связь БД и XML, производим сравнение
        $directories = $this->directoryParse($dbDirectory, $this->xmlDirectory);

        // Сохраняем результаты
        $dbDirectory->save($directories);

        return $this->answer();
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
}
