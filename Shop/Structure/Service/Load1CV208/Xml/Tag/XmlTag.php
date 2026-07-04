<?php

namespace Shop\Structure\Service\Load1CV208\Xml\Tag;

use Ideal\Field\Url\Model;
use Shop\Structure\Service\Load1CV208\Xml\AbstractXml;

class XmlTag extends AbstractXml
{
    /** @var string путь к списку тегов в XML */
    public $part = 'Tegi';

    /** @var array Привязка товаров к тегам (ключ — id товара, значение — ids тегов) */
    public $tags = [];

    /**
     * Парсинг xml выгрузки Tegi__*.xml
     *
     * @return array двумерный массив ключ - id_1c, значения - транслитированные ключи и данные из xml
     */
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

        // Заполняем массив привязки товаров к тегам
        $this->tags = [];
        foreach ($this->data as $tag) {
            $this->tags[$tag['goodId1c']][] = Model::translitUrl($tag['tag']);
        }

        // Оставляем только уникальные теги
        $data = [];
        foreach ($this->data as $val) {
            if (!empty($val['tag'])) {
                $key = Model::translitUrl($val['tag']);
                if (isset($val['parentTag']) && !empty($val['parentTag'])) {
                    $parentKey = Model::translitUrl($val['parentTag']);
                    // Ищем среди уже существующих данных указанный тег и присоединяем в случае успешности поиска
                    self::appendChild($parentKey, $val['parentTag'], $val['tag'], $key, $data);
                } else {
                    $data[$key]['name'] = $val['tag'];
                    $data[$key]['url'] = $key;
                }
            }
        }

        // Делаем из многомерного массива одномерный, проставляем всем lvl и меняем ключи
        $this->data = [];
        self::getCleanData($data);

        return $this->data;
    }

    /**
     * Рекурсивный метод осуществляющий поиск и объединение рогдителя и предка тегов
     *
     * @param string $parentKey - ключ родительского элемента в массиве
     * @param array<string, mixed> $data - массив данных для поиска
     * @param string $parentTag - наименование родительского тега
     * @param string $tag - текущий тег для присоединения
     * @param string $key - ключ присоеденяемых данных
     * @param bool $needAppend - признак надобности добавления дочернего тега
     *
     * @return bool $needAppend - признак надобности добавления дочернего тега
     */
    private function appendChild($parentKey, $parentTag, $tag, $key, array &$data, $needAppend = true)
    {
        if (isset($data[$parentKey])) {
            if (!isset($data[$parentKey]['child'][$key])) {
                $data[$parentKey]['child'][$key]['name'] = $tag;
                $data[$parentKey]['child'][$key]['url'] = $key;
            }

            $needAppend = false;
        } elseif (isset($data['child']) && is_array($data['child']) && (isset($data['child']) && $data['child'] !== [])) {
            foreach (array_keys($data['child']) as $key) {
                $needAppend = self::appendChild($parentKey, $parentTag, $tag, $key, $data['child'][$key], $needAppend);
            }
        }

        if ($needAppend) {
            $data[$parentKey]['name'] = $parentTag;
            $data[$parentKey]['url'] = $parentKey;
            $data[$parentKey]['child'][$key]['name'] = $tag;
            $data[$parentKey]['child'][$key]['url'] = $key;
        }

        return $needAppend;
    }

    /**
     * Перебирает данные многомерного массива для формирования одномерного,
     * проставляет всем элментам lvl и меняет ключи
     *
     * @param array $data - Многомерный иерархический массив данных по тегам
     * @param string $parentUrl - Адрес родительского элемента
     * @param integer $lvl - Уровень вложенности элемента
     */
    private function getCleanData($data, string $parentUrl = '', $lvl = 1): void
    {
        if ($parentUrl !== '' && $parentUrl !== '0') {
            $parentUrl .= '/';
        }

        foreach ($data as $key => $value) {
            $url = $parentUrl . $key;
            $this->data[$url] = ['lvl' => $lvl, 'name' => $value['name'], 'url' => $value['url'], 'is_active' => 1];
            if (isset($value['child'])) {
                self::getCleanData($value['child'], $url, $lvl + 1);
            }
        }
    }
}
