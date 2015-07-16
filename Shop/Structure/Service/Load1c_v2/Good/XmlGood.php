<?php
namespace Shop\Structure\Service\Load1c_v2\Good;

use Shop\Structure\Service\Load1c_v2\AbstractXml;
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

    /** @var array подготовленные данные из выгрузки. Ключ - id_1c, значение - данные из xml */
    protected $data;

    /** @var array переводчик */
    protected $translate = array(
        'Ид' => 'id_1c',
        'Артикул' => 'articul',
        'Наименование' => 'name',
        'Описание' => 'description'
    );

    /**
     * Парсинг xml выгрузки import.xml
     *
     * @return array двумерный массив ключ - id_1c, значения - транслитированные ключи и данные из xml
     */
    public function parse()
    {
        foreach ($this->xml[0] as $item) {
            $id = (string)  $item->{'Ид'};
            $this->data[$id] = array();

            // todo xpath необходимых значений в ноде
            foreach ($item->ЗначенияРеквизитов->ЗначениеРеквизита as $param) {
                switch ($param->Наименование) {
                    case 'СсылкаНаСайте':
                        $this->data[$id]['url'] = (string) $param->Значение;
                        break;
                    case 'НельзяЗаказать':
                        $this->data[$id]['is_active'] = (string) $param->Значение == 'false' ? '1' : '0';
                        break;
                }
            }

            $catId = (string) $item->{'Группы'}->{'Ид'};
            $this->data[$id]['category_id'] = $catId != '' ? $catId : 'Load1c_default';
            foreach ($item as $key => $param) {
                $child = $param->children();
                if ($child->count() === 0 && array_key_exists($key, $this->translate)) {
                    $this->data[$id][$this->translate[$key]] = (string) $param;
                }
            }

            if (!isset($this->data[$id]['url'])) {
                $this->data[$id]['url'] = Url\Model::translitUrl($this->data[$id]['name']);
            }
        }

        return $this->data;
    }
}
