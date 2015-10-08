<?php
namespace Shop\Structure\Service\Load1cV2\Good;

use Shop\Structure\Service\Load1cV2\AbstractXml;
use Ideal\Field\Url;

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

    public $groups = array();

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
            $this->data[$k]['is_active'] = $val['is_active'] == 'false' ? '1' : '0';

            if (!isset($val['url']) || $val['url'] == '') {
                $this->data[$k]['url'] = Url\Model::translitUrl($val['name']);
            }

            if (isset($val['img']) && $val['img'] != '') {
                $entry = substr(basename($val['img']), 0, 2);
                $this->data[$k]['img'] = "/images/1c/{$entry}/" . basename($val['img']);
            }

            if (isset($val['imgs']) && $val['imgs'] != '') {
                $entry = substr(basename($val['imgs']), 0, 2);
                $this->data[$k]['imgs'] = "/images/1c/{$entry}/" . basename($val['imgs']);
            }
        }

        return $this->data;
    }
}
