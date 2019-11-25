<?php
namespace Shop\Structure\Service\Load1CV3\Xml\Good;

use Shop\Structure\Service\Load1CV3\Xml\AbstractXml;

class XmlGoodAbstract extends AbstractXml
{
    /** @var string путь к товарам в XML */
    public $part = 'Каталог/Товары';

    /** @var array Привязка товаров к группам (ключ — id товара, значение — ids групп */
    public $groups = array();

    /**
     * Парсинг xml выгрузки import.xml
     *
     * @return array двумерный массив ключ - id_1c, значения - транслитированные ключи и данные из xml
     */
    public function parse()
    {
        if (isset($this->xml[0])) {
            $this->xml = $this->xml[0];
        }
        parent::parse();

        $this->data = $this->filterData($this->data);

        // Заполняем массив привязки товаров к группам
        $this->groups = array();
        foreach ($this->data as $k => $good) {
            $this->groups[$good['id_1c']] = $good['groups'];
            // Убираем список групп, т.к. он не должен использоваться при сохранении товара в БД
            unset($this->data[$k]['groups']);
        }

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
