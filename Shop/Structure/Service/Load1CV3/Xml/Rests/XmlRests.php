<?php
namespace Shop\Structure\Service\Load1CV3\Xml\Rests;

use Shop\Structure\Service\Load1CV3\Xml\AbstractXml;

class XmlRests extends AbstractXml
{
    /** @var string путь к категориям в XML */
    public $part = 'ПакетПредложений/Предложения';

    public function parse()
    {
        // Идентификатор главного склада для получения остатков
        if (empty($_SESSION['main_stock_id'])) {
            throw new \RuntimeException('В настройках обмена не задан идентификатор главного склада main_stock_id');
        }
        $mainStockId = $_SESSION['main_stock_id'];

        $fields = str_replace('{Ид}', $mainStockId, $this->configs['fields']);
        $this->configs['fields'] = $fields;

        parent::parse();

        return $this->data;
    }
}
