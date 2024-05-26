<?php
namespace Shop\Structure\Service\Load1CV3\Models;

use Ideal\Core\Config;
use Shop\Structure\Service\Load1CV3\Db\Prices\DbPrices;
use Shop\Structure\Service\Load1CV3\ModelAbstract;
use Shop\Structure\Service\Load1CV3\Xml\Prices\XmlPrices;
use Shop\Structure\Service\Load1CV3\Xml\Xml;

class PricesModel extends ModelAbstract
{
    protected XmlPrices $xmlPrices;

    public function init(): void
    {
        $this->setInfoText('Обработка цен (prices)');
        $this->setSort(1000);

        // инициализируем модель категорий в XML - XmlCategory
        $xml = new Xml($this->filename);
        $this->xmlPrices = new XmlPrices($xml);
        $this->isOnlyUpdate = $this->xmlPrices->updateInfo();
    }

    /**
     * Запуск процесса обработки файлов prices_*.xml
     *
     * @param int $packageNum Номер пакета
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($packageNum): array
    {
        $this->packageNum = $packageNum;

        // инициализируем модель предложений в БД - DbOffer
        $dbPrices = new DbPrices();

        // Устанавливаем связь БД и XML
        $prices = $this->parse($dbPrices, $this->xmlPrices);

        $dbPrices->save($prices);

        return $this->answer();
    }

    /**
     * Преобразование XML выгрузки в массив и сравнение с данными из БД
     *
     * @param DbPrices $dbPrices
     * @param XmlPrices $xmlPrices
     *
     * @return array двумерный массив с данными о ценах после сведения XML и БД
     */
    protected function parse($dbPrices, $xmlPrices)
    {
        // Забираем цены из БД
        $dbResult = $dbPrices->parse();

        $xmlResult = $xmlPrices->parse();

        if (empty($xmlResult)) {
            $xmlResult = array();
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
        $result = array();
        foreach ($xmlResult as $k => $val) {
            $val['price_old'] = '0';
            $whatIsThat = substr_count($k, '#') === 1 ? 'offers' :  'goods';
            if (!isset($dbResult[$k])) {
                $result[$k] = $val;
                $this->answer['add']++;
                $this->answer['tmpResult'][$whatIsThat]['insert'][$k] = 1;
                continue;
            }

            $res = array_diff_assoc($val, $dbResult[$k]);
            if (count($res) > 0) {
                $result[$k] = $res;
                $result[$k]['ID'] = $dbResult[$k]['ID'];
                $result[$k]['good_id'] = $dbResult[$k]['good_id'];
                $this->answer['update']++;
                $this->answer['tmpResult'][$whatIsThat]['update'][$k] = 1;
            }
        }

        return $result;
    }
}
