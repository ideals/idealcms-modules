<?php
namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Service\Load1CV3\Db\Order\DbOrder;
use Shop\Structure\Service\Load1CV3\Xml\Order\XmlOrder;
use Shop\Structure\Service\Load1CV3\Xml\Xml;

class DocumentsModel
{
    /** @var array Массив содержащий структурированный ответ по факту обработки файла */
    protected $answer = array(
        'infoText' => 'Обработка заказов из %s',
        'successText' => 'Добавлено: %d<br />Обновлено: %d',
        'add' => 0,
        'update' => 0
    );

    /** @var array Общие настройки для всего процесса обмена */
    public $exchangeConfig = array();

    public function __construct($exchangeConfig)
    {
        $this->exchangeConfig = $exchangeConfig;
    }

    /**
     * Запуск процесса обработки файлов ImagesFile_*.xml
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
        if (strlen($packageNum) <= 2) {
            $this->answer['infoText'] = sprintf(
                $this->answer['infoText'],
                'пакета №' . $packageNum
            );
        } else {
            $packageNum = 'корневой директории';
            $this->answer['infoText'] = sprintf(
                $this->answer['infoText'],
                $packageNum
            );
        }

        $xml = new Xml($filePath);

        // инициализируем модель заказов в XML - XmlOrder
        $xmlOrder = new XmlOrder($xml);
        $xmlOrder->setExchangeConfig($this->exchangeConfig);

        if ($xmlOrder->validate()) {
            // Инициализируем модель заказов в БД - DbOrder
            $dbOrder = new DbOrder();

            // Устанавливаем связь БД и XML
            $orders = $this->parse($dbOrder, $xmlOrder);

            // Записываем обновлённые заказы в БД
            $dbOrder->save($orders);

            // Уведомление пользователя о количестве добавленных, удалённых и обновлённых категорий
            $answer = $this->answer();
        } else {
            $answer = array(
                'infoText' => 'Обработка заказов',
                'successText'   => 'Заказов нет в файле импорта',
            );
        }
        return $answer;
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
     * @param DbOrder $dbOrder объект заказов БД
     * @param XmlOrder $xmlOrder объект заказов XML
     *
     * @return array двумерный массив с данными о заказах после сведения XML и БД
     */
    public function parse($dbOrder, $xmlOrder)
    {
        // Забираем результаты заказов из xml
        $xmlResult = $xmlOrder->parse();

        // Получаем ключи заказов для выборки из базы
        $orderKeys = array_keys($xmlResult);

        // Забираем результаты заказов из БД
        $dbOrder->setOrderKeys($orderKeys);
        $dbResult = $dbOrder->parse();

        $xmlAddDiff = array_diff_key($xmlResult, $dbResult);
        $this->answer['add'] = count($xmlAddDiff);
        $xmlAddDiff = array_flip(array_keys($xmlAddDiff));
        $this->answer['tmpResult']['documents']['insert'] = $xmlAddDiff;

        $xmlUpdateDiff = array_intersect_key($xmlResult, $dbResult);
        $this->answer['update'] = count($xmlUpdateDiff);
        $xmlUpdateDiff = array_flip(array_keys($xmlUpdateDiff));
        $this->answer['tmpResult']['documents']['update'] = $xmlUpdateDiff;

        // Отмечаем элементы для обновления
        foreach ($xmlResult as $key => &$xmlResultElement) {
            if (isset($dbResult[$key])) {
                $xmlResultElement['update'] = true;
                $end = end($dbResult[$key]);
                $xmlResultElement['orderId'] = $end['id'];
            }
        }

        return $xmlResult;
    }
}