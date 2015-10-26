<?php
namespace Shop\Structure\Service\Load1cV2\Good;

use Shop\Structure\Service\Load1cV2\AbstractXml;
use Ideal\Field\Url;

class XmlGood extends AbstractXml
{
    /** @var string путь к категориям в XML */
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
        $this->xml = $this->xml[0];
        parent::parse();

        foreach ($this->data as $k => $val) {
            $this->data[$k]['is_active'] = $val['is_active'] == 'false' ? '1' : '0';

            if (empty($val['url'])) {
                $this->data[$k]['url'] = Url\Model::translitUrl($val['name']);
            }

            if (!empty($val['img'])) {
                $entry = substr(basename($val['img']), 0, 2);
                $this->data[$k]['img'] = "/images/1c/{$entry}/" . basename($val['img']);
            }

            if (!empty($val['imgs'])) {
                if (is_array($val['imgs'])) {
                    foreach ($val['imgs'] as $img) {
                        $entry = substr(basename($img), 0, 2);
                        $this->data[$k]['imgs'][] = "/images/1c/{$entry}/" . basename($img);
                    }
                    // Список дополнительных картинок храним в JSON
                    $this->data[$k]['imgs'] = json_encode($this->data[$k]['imgs'], JSON_UNESCAPED_UNICODE);
                } else {
                    $this->data[$k]['imgs'] = '';
                }
            }
        }

        // Заполняем массив привязки товаров к группам
        $this->groups = array();
        foreach ($this->data as $k => $good) {
            $this->groups[$good['id_1c']] = $good['groups'];
            // Убираем список групп, т.к. он не должен использоваться при сохранении товара в БД
            unset($this->data[$k]['groups']);
        }

        return $this->data;
    }
}
