<?php
namespace Shop\Structure\Service\Load1CV208\Xml\Directory;

use Shop\Structure\Service\Load1CV208\Xml\AbstractXml;

class XmlDirectory extends AbstractXml
{
    /** @var string путь к категориям в XML */
    public $part = 'Классификатор/Свойства[`Свойство/ТипЗначений="Справочник"]';

    /**
     * Парсинг xml выгрузки import.xml
     *
     * @return array двумерный массив ключ - id_1c, значения - транслитированные ключи и данные из xml
     */
    public function parse()
    {
        parent::parse();
        $tmp = $this->data;
        $this->data = array();

        foreach ($tmp as $key => $item) {
            if (!isset($item['dir_values'])) {
                unset($this->data[$key]);
                continue;
            }
            if (!empty($item['dir_values'])) {
                foreach ($item['dir_values'] as $dict) {
                    $this->data[$dict['dir_value_id']] = $dict;
                    $this->data[$dict['dir_value_id']]['dir_id_1c'] = $key;
                    $this->data[$dict['dir_value_id']]['name'] = $item['name'];
                }
            }
        }

        return $this->data;
    }
}
