<?php
namespace Shop\Structure\Service\Load1CV3\Xml\NomenclProSov;

use Shop\Structure\Service\Load1CV3\Xml\Good\XmlGood;

class XmlNomenclProSov extends XmlGood
{
    /** @var string путь к категориям в XML */
    public $part = 'НоменклатураПродаваемаяСовместно';

    public function parse()
    {
        $id = 0;
        foreach ($this->xml as $item) {
            $this->data[$id] = array();

            $this->registerNamespace($item);

            $this->updateFromConfig($item, $id);
            $id++;
        }

        return $this->data;
    }
}
