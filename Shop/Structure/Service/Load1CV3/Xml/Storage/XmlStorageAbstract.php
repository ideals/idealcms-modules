<?php
namespace Shop\Structure\Service\Load1CV3\Xml\Storage;

use Shop\Structure\Service\Load1CV3\Xml\AbstractXml;

class XmlStorageAbstract extends AbstractXml
{
    /** @var string путь к складам в XML */
    public $part = 'Классификатор/Склады';

    /**
     * @return array двумерный массив ключ - id_1c, значения - транслитированные ключи и данные из xml
     */
    public function parse()
    {
        if (isset($this->xml[0])) {
            $this->xml = $this->xml[0];
        }
        parent::parse();

        $this->data = $this->filterData($this->data);

        return $this->data;
    }

    /**
     * Метод-заглушка, для кастомной фильтрации выгружаемых из 1С товаров
     *
     * @param array $data
     * @return array
     */
    public function filterData($data)
    {
        foreach ($data as $k => $val) {
            $data[$k]['is_active'] = $val['is_active'] === 'false' ? '1' : '0';
        }

        return $data;
    }
}
