<?php
namespace Shop\Structure\Service\Load1CV3\Xml\Order;

use Shop\Structure\Service\Load1CV3\Xml\AbstractXml;
use Shop\Structure\Service\Load1CV3\Xml\Xml;

class XmlOrderAbstract extends AbstractXml
{
    /** @var string путь к данным заказов в XML */
    public $part = '*';

    /** Общие настройки для всего процесса обмена */
    public array $exchangeConfig = [];

    /**
     * Преобразование XML выгрузки к массиву схожему с массивом данных из базы
     *
     * @return array двумерный массив данных
     */
    public function parse()
    {
        $this->recursiveParse($this->xml);
        foreach ($this->data as $k => $val) {
            // Так как в выгрузке стоит флаг удаления, а у нас - активности, то инвертируем его
            $this->data[$k]['is_active'] = $val['is_active'] === 'false' ? '1' : (int)$val['is_active'];
        }
        $this->data = $this->postParse($this->data);

        return $this->data;
    }

    /**
     * Подмена значений конфигурационного файла выгрузки
     */
    public function updateConfigs()
    {
        $this->configs['fields'] = array_merge($this->configs['fields'], $this->configs['updateDbFields']);
    }

    /**
     * Приведение XML выгрузки к двумерному массиву
     *
     * @param Xml $groupsXML - узел для преобразования
     */
    protected function recursiveParse($groupsXML)
    {
        if (empty($groupsXML)) {
            return;
        }

        foreach ($groupsXML->{'Контейнер'} as $container) {
            $val = [];
            foreach ($container->{'Документ'} as $child) {
                switch ((string) $child->{'ХозОперация'}) :
                    case 'Заказ товара':
                        $val = $this->parseOrder($child, $val);
                        $val['payments'] = $val['payments'] ?? [];
                        break;
                    case 'Выплата безналичных денег':
                        // @todo получение идентификаторов методов оплаты
                        $val = $this->parsePay($child, $val, 1);
                        break;
                    case 'Выплата наличных денег':
                        $val = $this->parsePay($child, $val, 2);
                        break;
                    case 'Эквайринговая операция':
                        $val = $this->parsePay($child, $val, 3);
                        break;
                    case 'Отпуск товара':
                        $val = $this->parseShipment($child, $val);
                        break;
                    default: // Непонятный документ, пропускаем и логируем
                        continue 2;
                endswitch;
            }

            $this->data[$val['orderId1c']] = $this->setStatus($val);
        }
    }

    /**
     * @param array $exchangeConfig
     */
    public function setExchangeConfig($exchangeConfig)
    {
        $this->exchangeConfig = $exchangeConfig;
    }

    /**
     * @inheritdoc
     */
    public function validate()
    {
        return isset($this->xml->{'Контейнер'});
    }

    public function getFields()
    {
        return $this->configs['fields'];
    }

    /**
     * Дополнительная обработка данных после извлечения их из XML
     *
     * @param array $data
     * @return array
     */
    protected function postParse($data)
    {
        return $data;
    }

    protected function parseOrder($child, $val): array
    {
        $id = (string) $child->{'Ид'};
        $namespaces = $child->getDocNamespaces();

        if (isset($namespaces[''])) {
            $defaultNamespaceUrl = $namespaces[''];
            $child->registerXPathNamespace('default', $defaultNamespaceUrl);
        }

        $this->updateFromConfig($child, $id);

        return $this->data[$id];
    }

    /**
     * Обработка оплаты для соответствующего заказа
     */
    protected function parsePay($child, array $val, int $method): array
    {
        $val['payment_sum'] = ($val['payment_sum'] ?? 0) + (float) $child->{'Сумма'};
        $val['payments'][] = [
            'id_1c' => (string) $child->{'Ид'},
            'order_id' => 1,
            'orderId1c' => (string) $child->{'Основание'},
            'payment_method_id' => $method,
            'date' => $child->{'Дата'} . ' ' . $child->{'Время'},
            'signature' => (string) $child->{'Ид'},
            'price' => (int) (((float) $child->{'Сумма'}) * 100),
        ];

        return $val;
    }

    /**
     * Обработка отгрузки товара для соответствующего заказа
     */
    protected function parseShipment($child, $val): array
    {
        $val['shipment_sum'] = ($val['shipment_sum'] ?? 0) + (float) $child->{'Сумма'};

        return $val;
    }

    protected function setStatus($val): array
    {
        // todo на основании реквизитов Проведён, Оплачен, Отменён, здесь можно собрать статус заказа
        unset($val['discard']);

        return $val;
    }
}
