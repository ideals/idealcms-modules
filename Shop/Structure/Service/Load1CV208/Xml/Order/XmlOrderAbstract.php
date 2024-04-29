<?php
namespace Shop\Structure\Service\Load1CV208\Xml\Order;

use Shop\Structure\Service\Load1CV208\Xml\AbstractXml;

class XmlOrderAbstract extends AbstractXml
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
        foreach ($this->data as $k => &$val) {
            // Так как в выгрузке стоит флаг удаления, а у нас - активности, то инвертируем его
            $val['is_active'] = $val['is_active'] == 'false' ? '1' : (int)$val['is_active'];
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

        foreach ($groupsXML->{'Документ'} as $child) {
            $child = $this->filterXml($child);
            if (empty($child)) {
                continue;
            }
            $id = (string) $child->{'Ид'};
            $namespaces = $child->getDocNamespaces();

            if (isset($namespaces[''])) {
                $defaultNamespaceUrl = $namespaces[''];
                $child->registerXPathNamespace('default', $defaultNamespaceUrl);
            }

            $this->updateFromConfig($child, $id);
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

    public function getFields()
    {
        return $this->configs['fields'];
    }

    public function filterXml($child)
    {
        return $child;
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
}
