<?php
namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Service\Load1CV3\Db\Order\DbOrder;
use Shop\Structure\Service\Load1CV3\ModelAbstract;
use Shop\Structure\Service\Load1CV3\Xml\Order\XmlOrder;
use Shop\Structure\Service\Load1CV3\Xml\Xml;

class OrdersModel extends ModelAbstract
{
    protected XmlOrder $xmlOrder;

    public function init(): void
    {
        $this->setInfoText('Обработка заказов');
        $this->setSort(2000);

        // инициализируем модель заказов в XML - XmlOrder
        $xml = new Xml($this->filename);
        $this->xmlOrder = new XmlOrder($xml);
        $this->xmlOrder->setExchangeConfig($this->exchangeConfig);
    }

    /**
     * @inheritDoc
     */
    public function startProcessing($packageNum): array
    {
        // Определяем пакет для отдачи правильного текста в ответе
        $this->answer['infoText'] = sprintf(
            $this->answer['infoText'],
            $this->packageNum
        );

        if ($this->xmlOrder->validate()) {
            // Инициализируем модель заказов в БД - DbOrder
            $dbOrder = new DbOrder();

            // Устанавливаем связь БД и XML
            $orders = $this->parse($dbOrder, $this->xmlOrder);

            // Записываем обновлённые заказы в БД
            $dbOrder->save($orders);

            // Уведомление пользователя о количестве добавленных, удалённых и обновлённых категорий
            $answer = $this->answer();
        } else {
            $answer = [
                'infoText' => 'Обработка заказов',
                'successText' => 'Заказов нет в файле импорта',
            ];
        }

        return $answer;
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
        $fields = $xmlOrder->getFields();

        // Получаем ключи заказов для выборки из базы
        $orderKeys = array_keys($xmlResult);

        // Забираем результаты заказов из БД
        $dbOrder->setOrderKeys($orderKeys);
        $dbResult = $dbOrder->parse($fields);

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
                $xmlResultElement['ID'] = $dbResult[$key]['ID'];
                $xmlResultElement = $this->beforeUpdate($dbResult[$key], $xmlResultElement);
            }
        }

        return $xmlResult;
    }

    private function beforeUpdate(array $old, array $new): array
    {
        if ($new['payment_sum'] == 0 || $old['payment_bank'] > 0) {
            // todo расчёт банковских платежей из таблицы orderpay
            $new['payment_sum'] = $old['payment_bank'];
        }

        return $new;
    }
}
