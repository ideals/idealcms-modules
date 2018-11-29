<?php
namespace Shop\Structure\Service\Load1CV3\Xml\Order;

use Shop\Structure\Service\Load1CV3\Xml\AbstractXml;

class XmlOrder extends AbstractXml
{
    /** @var string путь к данным заказов в XML */
    public $part = '*';

    /** @var array Общие настройки для всего процесса обмена */
    public $exchangeConfig = array();

    /**
     * Преобразование XML выгрузки к массиву схожему с массивом данных из базы
     *
     * @return array двумерный массив данных
     */
    public function parse()
    {
        $this->recursiveParse($this->xml);
        $data = array();
        foreach ($this->data as $k => $val) {
            $val['is_active'] = $val['is_active'] == 'false' ? '1' : (int)$val['is_active'];
            if (isset($this->exchangeConfig['clear_comment']) && !empty($this->exchangeConfig['clear_comment'])) {
                $clearCommentPatterns = explode("\n", $this->exchangeConfig['clear_comment']);
                foreach ($clearCommentPatterns as $pattern) {
                    $val['order_comment'] = preg_replace($pattern, '', $val['order_comment']);
                }

                // убираем лишние пробельные символы после всех чисток
                $val['order_comment'] = preg_replace('/\h\h/', ' ', $val['order_comment']);
                $val['order_comment'] = preg_replace('/\v\v/', "\n", $val['order_comment']);
                $val['order_comment'] = trim($val['order_comment']);
            }
            $goods = $val['goods'];
            unset($val['goods']);
            foreach ($goods as $good) {
                $data[$val['orderId1c']][$good['good_id']] = array_merge($val, $good);
            }
        }
        $this->data = $data;
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
        if (!empty($groupsXML)) {
            foreach ($groupsXML->{'Документ'} as $child) {
                $id = (string) $child->{'Ид'};
                $namespaces = $child->getDocNamespaces();

                if (isset($namespaces[''])) {
                    $defaultNamespaceUrl = $namespaces[''];
                    $child->registerXPathNamespace('default', $defaultNamespaceUrl);
                }

                parent::updateFromConfig($child, $id);
            }
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
        return isset($this->xml->{'Документ'});
    }
}
