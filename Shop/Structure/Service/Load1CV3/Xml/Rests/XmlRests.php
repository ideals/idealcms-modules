<?php
namespace Shop\Structure\Service\Load1CV3\Xml\Rests;

use Shop\Structure\Service\Load1CV3\Xml\AbstractXml;

class XmlRests extends AbstractXml
{
    /** @var string путь к категориям в XML */
    public $part = 'ПакетПредложений/Предложения';

    /** @var string Идентификатор главного склада для получения остатков */
    public $mainStockId = '';

    public function parse()
    {
        $fields = str_replace('{Ид}', $this->mainStockId, $this->configs['fields']);
        $this->configs['fields'] = $fields;

        parent::parse();

        return $this->data;
    }

    /**
     * @param string $mainStockId
     */
    public function setMainStockId($mainStockId)
    {
        $this->mainStockId = $mainStockId;
    }
}
