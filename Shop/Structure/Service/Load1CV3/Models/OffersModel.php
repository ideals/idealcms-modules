<?php
namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Service\Load1CV3\Db\Offer\DbOffer;
use Shop\Structure\Service\Load1CV3\ModelAbstract;
use Shop\Structure\Service\Load1CV3\Xml\Directory\XmlDirectory;
use Shop\Structure\Service\Load1CV3\Db\Directory\DbDirectory;
use Shop\Structure\Service\Load1CV3\Xml\Offer\XmlOffer;
use Shop\Structure\Service\Load1CV3\Xml\Xml;

class OffersModel extends ModelAbstract
{
    protected XmlOffer $xmlOffers;

    public function init(): void
    {
        $this->setInfoText('Обработка офферов (offers)');
        $this->setSort(90);

        // инициализируем модель предложений в XML - XmlOffer
        $xml = new Xml($this->filename);
        $this->xmlOffers = new XmlOffer($xml);
        $this->isOnlyUpdate = $this->xmlOffers->updateInfo();
    }

    /**
     * Запуск процесса обработки файлов offers_*.xml
     *
     * @param int $packageNum Номер пакета
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($packageNum): array
    {
        $this->packageNum = $packageNum;

        // Файл offers_*.xml может быть двух типов.
        // Корневой содержит информацию о свойствах товаров.
        // Пакетный, содержит информацию о торговых предложениях.
        // Определяем тип файла и в зависимости от этого запускаем нужную обработку.

        // Определяем пакет для отдачи правильного текста в ответе
        $this->answer['infoText'] = sprintf(
            $this->answer['infoText'],
            $this->packageNum
        );

        // инициализируем модель предложений в БД - DbOffer
        $dbOffers = new DbOffer();

        // Устанавливаем связь БД и XML
        $offers = $this->offersParse($dbOffers, $this->xmlOffers);

        $dbOffers->save($offers);

        return $this->answer();
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
     * Если есть в БД всегда обновляем, чтобы была возможность деактивировать отсуствующие
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
            $result[$k] = $val;
            $result[$k]['ID'] = $dbResult[$k]['ID'];
            $result[$k]['good_id'] = $dbResult[$k]['good_id'];
            $this->answer['update']++;
            $this->answer['tmpResult']['offers']['update'][$k] = 1;
        }
        return $result;
    }
}
