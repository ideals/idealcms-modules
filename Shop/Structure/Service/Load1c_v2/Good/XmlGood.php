<?php
namespace Shop\Structure\Service\Load1c_v2\Good;

use Shop\Structure\Service\Load1c_v2\AbstractXml;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:32
 */

class XmlGood extends AbstractXml
{
    /** @var string путь к категориям в XML */
    public $part = 'Каталог/Товары';

    /**
     * Парсинг xml выгрузки import.xml
     *
     * @return array двумерный массив ключ - id_1c, значения - транслитированные ключи и данные из xml
     */
    public function parse()
    {
        $this->xml = $this->xml[0];
        parent::parse();

        foreach ($this->data as $k => $val) {
            if ($val['category_id'] == '') {
                $this->data[$k]['category_id'] = 'Load1c_default';
            }

            if ('' == $val['url']) {
                unset($this->data[$k]['url']);
            }

            $this->data[$k]['is_active'] = $val['is_active'] == 'false' ? '1' : '0';
        }

        return $this->data;
    }
}
