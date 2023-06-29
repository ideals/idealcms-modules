<?php
namespace Shop\Structure\Service\Load1CV208\Xml\Category;

use Shop\Structure\Service\Load1CV208\Xml\AbstractXml;

class XmlCategoryAbstract extends AbstractXml
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
        foreach ($this->data as $k => $val) {
            $this->data[$k]['is_active'] = $val['is_active'] == 'false' ? '1' : (int)$val['is_active'];
        }
        return $this->data;
    }

    /**
     * Запись в xml новых данных по ИД
     *
     * @param $elem array массив значений для добавления в xml node
     */
    public function updateElement($elem)
    {
        $path = '//' . $this->ns . '*[' . $this->ns . 'Ид="' . $elem['Ид'] . '"]';
        unset($elem['id_1c']);
        $element = $this->xml->xpath($path);

        foreach ($elem as $key => $value) {
            if (!isset($element[0]->{$key})) {
                $element[0]->addChild($key, $value);
            }
        }
    }

    /**
     * Подмена значений конфигурационного файла выгрузки
     */
    public function updateConfigs()
    {
        $this->configs['fields'] = array_merge($this->configs['fields'], $this->configs['updateDbFields']);
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
            $path .= '[' . $this->ns . 'Ид="' . $element['parent'] . '"]';
        // Если нет родителя - добавляем на 1ый уровень
        } else {
            $path = '//' . $this->ns . 'Классификатор/' . $this->ns . 'Группы';
        }

        $parent = $this->xml->xpath($path);

        if (!isset($parent[0]->{'Группа'})) {
            $parent[0] = $parent[0]->addChild('Группы');
        }

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
     * @param int $lvl уровень вложенности
     * @return array одномерный массив
     */
    protected function recursiveParse($groupsXML, $i = 0, $lvl = 1)
    {
        $groups = array();
        if (!empty($groupsXML)) {
            foreach ($groupsXML->{'Группа'} as $child) {
                if ((string) $child->{'Ид'} == 'not-1c') {
                    $id = $i++;
                } else {
                    $id = (string) $child->{'Ид'};
                }

                $this->data[$id]['lvl'] = $lvl;
                $namespaces = $child->getDocNamespaces();

                if (isset($namespaces[''])) {
                    $defaultNamespaceUrl = $namespaces[''];
                    $child->registerXPathNamespace('default', $defaultNamespaceUrl);
                }

                parent::updateFromConfig($child, $id);

                if ($child->{'Группы'}) {
                    $this->recursiveParse($child->{'Группы'}, $i, $lvl + 1);
                }
            }
        }
        return $groups;
    }
}
