<?php
namespace Shop\Structure\Service\Load1c_v2\Category;

use Shop\Structure\Service\Load1c_v2\AbstractXml;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:32
 */

class XmlCategory extends AbstractXml
{
    /** @var string путь к категориям в XML */
    public $part = 'Классификатор/Группы';

    /**
     * Преобразование XML выгрузки к массиву
     *
     * @return array двумерный массив данных
     */
    public function parse()
    {
        $this->recursiveParse($this->xml);
        return $this->data;
    }

    public function updateElement($array)
    {
        $path = '//' . $this->ns . '*[' . $this->ns . 'Ид="' . $array['Ид'] . '"]';
        unset($array['id_1c']);
        $element = $this->xml->xpath($path);

        foreach ($array as $key => $value) {
            if (!isset($element[0]->{$key})) {
                $element[0]->addChild($key, $value);
            }
        }
    }

    /**
     * Добавление в XML структуру нового элемента
     *
     * @param array $element данные о добавляемой категории
     */
    public function addChild($element)
    {
        $path = '//' . $this->ns . 'Группа';
        // Если есть родитель - выбираем его в XML и добавляем новые поля уже к нему
        if ($element['parent'] != null) {
            $path .= '[' . $this->ns . 'Ид="' . $element['parent'] . '"]/' . $this->ns . 'Группы';
        // Если нет родителя - добавляем на 1ый уровень
        } else {
            $path = '//' . $this->ns . 'Классификатор/' . $this->ns . 'Группы';
        }
        $parent = $this->xml->xpath($path);

        $newElement = $parent[0]->addChild('Группа');
        unset($element['parent']);

        foreach ($element as $key => $value) {
            $newElement->addChild($key, $value);
        }
    }

    /**
     * Приведение XML выгрузки к одномерному массиву
     *
     * @param \SimpleXMLElement $groupsXML - узел для преобразования
     * @param int $i при повторном парсинге есть элементы с not-1c - им ставим порядковый ключ в массиве
     * @param string $parent родитель элемента - ключ id_1c
     * @param int $lvl уровень вложенности
     * @return array одномерный массив
     */
    protected function recursiveParse($groupsXML, $i = 0, $parent = '', $lvl = 1)
    {
        $groups = array();

        foreach ($groupsXML->{'Группа'} as $child) {
            if ((string)$child->{'Ид'} == 'not-1c') {
                $id = $i++;
            } else {
                $id = (string)$child->{'Ид'};
            }
            if ($parent != '') {
                $this->data[$id]['parent'] = $parent;
            }
            $this->data[$id]['lvl'] = $lvl;
            foreach ($child as $key => $field) {
                if ($key != 'Группы') {
                    $this->data[$id][(string) $key] = (string) $field;
                }
            }
            if ($child->{'Группы'}) {
                $this->recursiveParse($child->{'Группы'}, $i, $id, ++$lvl);
                $lvl--;
            }
        }
        return $groups;
    }
}
