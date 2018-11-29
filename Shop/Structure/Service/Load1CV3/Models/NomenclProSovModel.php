<?php
namespace Shop\Structure\Service\Load1CV3\Models;

use Ideal\Core\Db;
use Shop\Structure\Service\Load1CV3\Db\NomenclProSov\DbNomenclProSov;
use Shop\Structure\Service\Load1CV3\Xml\Xml;
use Shop\Structure\Service\Load1CV3\Xml\NomenclProSov\XmlNomenclProSov;

class NomenclProSovModel
{
    /** @var array Массив содержащий структурированный ответ по факту обработки файла */
    protected $answer = array(
        'infoText' => 'Обработка совместно продаваемых товаров из пакета № %d',
        'successText' => 'Добавлено: %d<br />Обновлено: %d',
        'add' => 0,
        'update' => 0
    );

    /**
     * Запуск процесса обработки файлов NomenclProSov_*.xml
     *
     * @param string $filePath Полный путь до обрабатываемого файла
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($filePath)
    {
        // Определяем пакет для отдачи правильного текста в ответе
        $dir = pathinfo($filePath, PATHINFO_DIRNAME);
        $dirParts = explode(DIRECTORY_SEPARATOR, $dir);
        $packageNum = (int) end($dirParts);
        $this->answer['infoText'] = sprintf(
            $this->answer['infoText'],
            $packageNum
        );

        $xml = new Xml($filePath);

        // Инициализируем модель совместно продаваемых товаров в БД - DbGood
        $dbNomenclProSov = new DbNomenclProSov();

        // Инициализируем модель совместно продаваемых товаров в XML - XmlGood
        $xmlNomenclProSov = new XmlNomenclProSov($xml);

        // Устанавливаем связь БД и XML и производим сравнение данных
        $nomenclProSov = $this->parse($dbNomenclProSov, $xmlNomenclProSov);

        // Сохраняем результаты
        $dbNomenclProSov->save($nomenclProSov);

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
     * @param DbNomenclProSov $dbNomenclProSov
     * @param XmlNomenclProSov $xmlNomenclProSov
     *
     * @return array двумерный массив с данными о ценах после сведения XML и БД
     */
    protected function parse($dbNomenclProSov, $xmlNomenclProSov)
    {
        $xmlResult = $xmlNomenclProSov->parse();

        if (empty($xmlResult)) {
            return array();
        }

        $trueXmlResult = array();
        foreach ($xmlResult as $item) {
            if (!empty($item['togetherSoldTo']) && !empty($item['togetherSoldThat'])) {
                $trueXmlResult[$item['togetherSoldTo']][] = $item['togetherSoldThat'];
            }
        }
        $db = Db::getInstance();
        $table = $dbNomenclProSov->getTable();
        $tablePostfix = $dbNomenclProSov->getTablePostfix();
        foreach ($trueXmlResult as $key => $value) {
            $ids1c = '\'' . implode('\',\'', $value) . '\'';
            $sql = "SELECT ID FROM {$table}{$tablePostfix} WHERE id_1c IN ({$ids1c}) AND is_active = 1";
            $select = $db->select($sql);
            $trueXmlResult[$key] = array('together_sold_goods' => '');
            if (!empty($select)) {
                foreach ($select as $item) {
                    $trueXmlResult[$key]['together_sold_goods'] .= ',' . $item['ID'];
                }
                $trueXmlResult[$key]['together_sold_goods'] = ltrim($trueXmlResult[$key]['together_sold_goods'], ',');
            }
        }
        $trueXmlResult = array_filter($trueXmlResult);

        // Забираем результаты товаров из БД
        $dbNomenclProSov->setGoodKeys(array_keys($trueXmlResult));
        $dbResult = $dbNomenclProSov->parse();

        return $this->diff($dbResult, $trueXmlResult);
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
            // Если из XML считали информацию о совместно продоваемых товарах для товара которого ещё нет в базе,
            // то вероятнее всего в XML указан не товар, то есть ошибка выгрузки. Не берём такие данные
            if (!isset($dbResult[$k])) {
                continue;
            }
            $res = array_diff_assoc($val, $dbResult[$k]);
            if (count($res) > 0) {
                $result[$k] = $res;
                $result[$k]['ID'] = $dbResult[$k]['ID'];
                $this->answer['update']++;
                $this->answer['tmpResult']['goods']['update'][$val['id_1c']] = 1;
            }
        }
        return $result;
    }
}