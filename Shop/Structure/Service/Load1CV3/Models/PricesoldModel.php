<?php
namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Service\Load1CV3\Db\Pricesold\DbPricesold;
use Shop\Structure\Service\Load1CV3\Xml\Pricesold\XmlPricesold;
use Shop\Structure\Service\Load1CV3\Xml\PricesoldFormatOld\XmlPricesoldFormatOld;
use Shop\Structure\Service\Load1CV3\Xml\Xml;

class PricesoldModel
{
    /** @var array Массив содержащий структурированный ответ по факту обработки файла */
    protected $answer = array(
        'infoText' => 'Обработка старых цен из пакета № %d',
        'successText' => 'Добавлено: %d<br />Обновлено: %d',
        'add' => 0,
        'update' => 0
    );

    /**
     * Запуск процесса обработки файлов pricesold_*.xml
     *
     * @param string $filePath Полный путь до обрабатываемого файла
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($filePath)
    {
        // Определяем пакет для отдачи правильного текста в ответе
        $dir = pathinfo($filePath, PATHINFO_DIRNAME);
        $dirParts = explode(DIRECTORY_SEPARATOR, $dir);
        $packageNum = end($dirParts);
        $this->answer['infoText'] = sprintf(
            $this->answer['infoText'],
            $packageNum
        );

        // получение xml с данными о старых ценах
        $xml = new Xml($filePath);
        $xmlPricesOld = new XmlPricesold($xml);
        if ($xmlPricesOld->validate()) {
            $this->priceOld($filePath);
        } else {
            $this->priceOldFormatOld($filePath);
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
     * Обработка информации по старым ценам в новом формате
     *
     * @param string $filePath Полный путь до обрабатываемого файла
     */
    protected function priceOld($filePath)
    {
        // получение xml с данными о старых ценах
        $xml = new Xml($filePath);

        // инициализируем модель старых цен в БД - DbPriceold
        $dbPricesOld = new DbPricesold();

        // инициализируем модель старых цен в XML - XmlPriceold
        $xmlPricesOld = new XmlPricesold($xml);

        // Устанавливаем связь БД и XML
        $pricesOld = $this->parse($dbPricesOld, $xmlPricesOld);

        $dbPricesOld->save($pricesOld);
    }

    /**
     * Обработка информации по старым ценам в новом формате
     *
     * @param string $filePath Полный путь до обрабатываемого файла
     */
    protected function priceOldFormatOld($filePath)
    {
        // получение xml с данными о старых ценах
        $xml = new Xml($filePath);

        // инициализируем модель старых цен в БД - DbPriceold
        $dbPricesOld = new DbPricesold();

        // инициализируем модель старых цен в XML - XmlPriceold
        $xmlPricesOld = new XmlPricesoldFormatOld($xml);

        // Устанавливаем связь БД и XML
        $pricesOld = $this->parse($dbPricesOld, $xmlPricesOld);

        $dbPricesOld->save($pricesOld);
    }

    /**
     * Преобразование XML выгрузки в массив и сравнение с данными из БД
     *
     * @param DbPricesold $dbPricesOld
     * @param XmlPricesold|XmlPricesoldFormatOld $xmlPricesOld
     *
     * @return array двумерный массив с данными о ценах после сведения XML и БД
     */
    protected function parse($dbPricesOld, $xmlPricesOld)
    {
        // Забираем старые цены из БД
        $dbResult = $dbPricesOld->parse();

        $xmlResult = $xmlPricesOld->parse();

        if (empty($xmlResult)) {
            $xmlResult = array();
        }

        // Умножаем старые цены на 100 длля хранения в БД
        array_walk($xmlResult, function ($v, $k) use (&$xmlResult) {
            if (isset($v['price_old'])) {
                $xmlResult[$k]['price_old'] *= 100;
            }
        });

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