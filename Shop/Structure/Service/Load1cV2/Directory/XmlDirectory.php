<?php
namespace Shop\Structure\Service\Load1cV2\Directory;

use Shop\Structure\Service\Load1cV2\AbstractXml;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:32
 */

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
            foreach ($item['dir_values'] as $dict) {
                $this->data[$dict['dir_value_id']] = $dict;
                $this->data[$dict['dir_value_id']]['dir_id_1c'] = $key;
                $this->data[$dict['dir_value_id']]['name'] = $item['name'];
            }
        }

        return $this->data;
    }
}
