<?php
namespace Shop\Structure\Service\Load1CV3\Xml\Good;

use Shop\Structure\Service\Load1CV3\Xml\AbstractXml;
use Ideal\Field\Url;

class XmlGood extends AbstractXml
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

        foreach ($this->data as $k => $val) {
            // Обрабатываем только товары из "Основной группы"
            if ($val['group'] == '1) Основная группа') {
                // Убираем информацию о группе из данных, она не нужна в базе
                unset($this->data[$k]['group']);

                $this->data[$k]['new_item'] = $val['new_item'] == 'Новинка' ? '1' : '0';
                $this->data[$k]['is_active'] = $val['is_active'] == 'false' ? '1' : '0';
                $this->data[$k]['possible_be_ordering_in_absence'] = '0';
                if ($val['possible_be_ordering_in_absence'] == 'Да') {
                    $this->data[$k]['possible_be_ordering_in_absence'] = '1';
                }

                if (empty($val['url'])) {
                    $this->data[$k]['url'] = Url\Model::translitUrl($val['name']);
                }

                // Разбираем представленный в выгрузке адрес на части, чтобы получился адрес относительно корня сайта
                if (!empty($val['url_full'])) {
                    $urlPath = parse_url($val['url_full'], PHP_URL_PATH);
                    $this->data[$k]['url_full'] = '/' . trim($urlPath, '/') . '/';
                }

                if (!empty($val['img'])) {
                    $entry = substr(basename($val['img']), 0, 2);
                    $this->data[$k]['img'] = "/images/1c/{$entry}/" . basename($val['img']);
                }

                if (is_array($val['imgs'])) {
                    if (!empty($val['imgs'])) {
                        $imgs = array();
                        foreach ($val['imgs'] as $img) {
                            $entry = substr(basename($img), 0, 2);
                            $imgs[] = "/images/1c/{$entry}/" . basename($img);
                        }
                        // Список дополнительных картинок храним в JSON
                        $this->data[$k]['imgs'] = json_encode($imgs);
                    } else {
                        $this->data[$k]['imgs'] = '';
                    }
                }

                if (isset($val['content']) && !empty($val['content'])) {
                    $val['content'] = str_replace(array("\r\n\r\n", "\r\r", "\n\n"), '</p>', $val['content']);
                    $val['content'] = nl2br($val['content']);
                    $paragraphs = explode('</p>', $val['content']);
                    if (count($paragraphs) > 1) {
                        foreach ($paragraphs as &$paragraph) {
                            $paragraph = '<p>' . $paragraph . '</p>';
                        }
                    }
                    $this->data[$k]['content'] = $val['content'] = implode('', $paragraphs);
                }
            } else {
                unset($this->data[$k]);
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
