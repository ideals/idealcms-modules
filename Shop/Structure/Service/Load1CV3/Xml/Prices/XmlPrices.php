<?php
namespace Shop\Structure\Service\Load1CV3\Xml\Prices;

use Shop\Structure\Service\Load1CV3\Xml\AbstractXml;

class XmlPrices extends AbstractXml
{
    /** @var string путь к категориям в XML */
    public $part = 'ПакетПредложений/Предложения';

    public function parse()
    {
        parent::parse();

        if (!empty($this->data)) {
            foreach ($this->data as $k => $value) {
                // Если в выгрузке дробное число с запятой - заменяем на точку
                // И помножаем на 100 для хранения в БД целочисленных данных
                $this->data[$k]['price'] = str_replace(',', '.', $value['price']) * 100;
            }
        }

        return $this->data;
    }
}
