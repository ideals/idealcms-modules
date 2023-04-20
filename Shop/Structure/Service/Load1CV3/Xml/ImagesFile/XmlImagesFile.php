<?php
namespace Shop\Structure\Service\Load1CV3\Xml\ImagesFile;

use Shop\Structure\Service\Load1CV3\Xml\Good\XmlGood;

class XmlImagesFile extends XmlGood
{
    /** @var string путь к категориям в XML */
    public $part = 'ImagesFile';

    public function parse()
    {
        $id = 0;
        $this->data = array();
        foreach ($this->xml as $item) {
            $this->data[$id] = array();

            $this->registerNamespace($item);

            $this->updateFromConfig($item, $id);
            $id++;
        }

        return $this->data;
    }
}
