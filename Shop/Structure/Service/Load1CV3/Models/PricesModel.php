<?php
namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Service\Load1CV3\Db\Prices\DbPrices;
use Shop\Structure\Service\Load1CV3\Xml\Prices\XmlPrices;
use Shop\Structure\Service\Load1CV3\Xml\Xml;

class PricesModel
{
    /** @var array Массив содержащий структурированный ответ по факту обработки файла */
    protected $answer = array(
        'infoText' => 'Обработка цен из пакета № %d',
        'successText' => 'Добавлено: %d<br />Обновлено: %d',
        'add' => 0,
        'update' => 0
    );

    /**
     * Запуск процесса обработки файлов prices_*.xml
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

        // получение xml с данными о ценах
        $xml = new Xml($filePath);

        // инициализируем модель предложений в БД - DbOffer
        $dbPrices = new DbPrices();

        // инициализируем модель категорий в XML - XmlCategory
        $xmlPrices = new XmlPrices($xml);

        // Устанавливаем связь БД и XML
        $prices = $this->parse($dbPrices, $xmlPrices);

        $dbPrices->save($prices);

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
