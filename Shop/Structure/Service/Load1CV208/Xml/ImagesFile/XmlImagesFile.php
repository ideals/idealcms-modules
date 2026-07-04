<?php

namespace Shop\Structure\Service\Load1CV208\Xml\ImagesFile;

use Shop\Structure\Service\Load1CV208\Xml\Good\XmlGood;

class XmlImagesFile extends XmlGood
{
    /** @var string путь к категориям в XML */
    public $part = 'ImagesFile';

    public function parse(): array
    {
        $id = 0;
        $this->data = [];
        foreach ($this->xml as $item) {
            $this->data[$id] = [];

            $this->registerNamespace($item);

            $this->updateFromConfig($item, $id);
            $id++;
        }

        return $this->data;
    }
}
