<?php
namespace Shop\Structure\Service\Load1c;


/**
 * Class ModGroups
 * @package Shop\Structure\Service\Load1c
 */
class ModGroups
{
    protected $groupsXML;
    protected $xml;

    /**
     * @param $groupsXML
     * @param $xml
     */
    public function __construct($groupsXML, $xml)
    {
        $this->groupsXML = $groupsXML;
        $this->xml = $xml;

        $rules = array(
            // Правила на создание групп
            'insert' => array( /*array(
                    'name' => 'TEST', Имя категории
                    'ID'   => 'insert-1', ID категории
                    'parent' => '', ID одителя
                )*/
            ),
            // Правила на удаление групп
            'delete' => array( //'id_1c_element_for_delete' => 1

            ),
            // Правила на перемещение групп
            'move' => array( /*array(
                    // менять только значения
                    'IDchild' => 'child_id', // ID категории которую переносим
                    'IDparent' => 'parent_id' // ID категории куда переносим
                )*/

            ),
            'plain' => array( // 'ID' // ID группы в которой все подгруппы переместятся в родительскую
            )
        );

        $this->moveIt($rules);

    }


    /**
     * Выполняет создание, перемещение и удаление групп в соответствии с правилами
     * @param $rules
     */
    protected function moveIt($rules)
    {

        // Создание новых групп
        foreach ($rules['insert'] as $v) {

            // Добавление группы в корень
            if ($v['parent'] == '') {
                $elem = $this->groupsXML->addChild('Группа');
                $elem->addChild('Ид', $v['ID']);
                $elem->addChild('Наименование', $v['name']);
                continue;
            }

            // Ищем группу-родитель, где нужно создать новую группу
            $parent = $this->groupsXML->xpath('//Группа[Ид="' . $v['parent'] . '"]');

            if (!isset($parent[0])) {
                // Если родителя нет - облом!!!
                echo 'Нету родителя :(<br />';
                print_r($v);
                exit;
            }

            // Проверяем, есть ли у нашего родителя ребёнки
            $elem = $parent[0]->{'Группы'};

            // Если ребёнков нет, добавляем тэг Группы
            if ($elem->count() == 0) {
                $elem = $parent[0]->addChild('Группы');
            }

            // Добавляем ребёнка
            $elem = $elem->addChild('Группа');
            $elem->addChild('Ид', $v['ID']);
            $elem->addChild('Наименование', $v['name']);

        }

        // Перемещение групп
        foreach ($rules['move'] as $v) {
            $child = $this->groupsXML->xpath('//Группа[Ид="' . $v['IDchild'] . '"]');
            if ($v['IDparent'] != '') {
                $parent = $this->groupsXML->xpath('//Группа[Ид="' . $v['IDparent'] . '"]');
                // Проверяем, есть ли у нашего родителя ребёнки
                $elem = $parent[0]->{'Группы'};

                // Если ребёнков нет, добавляем тэг Группы
                if ($elem->count() == 0) {
                    $elem = $parent[0]->addChild('Группы');
                }

                $domChild = dom_import_simplexml($child[0]);
                $domParent = dom_import_simplexml($parent[0]);
                $prnt = $domParent->getElementsByTagName('Группы');
                $prnt->item(0)->appendChild($domChild);
            } else {
                $domChild = dom_import_simplexml($child[0]);
                $domParent = dom_import_simplexml($this->groupsXML);
                $domParent->appendChild($domChild);
            }


        }

        // Переносит товар в родительскую группу, а подгруппы удаляет
        foreach ($rules['plain'] as $k => $v) {
            // Родительская группа
            $node = $this->groupsXML->xpath('//Группа[Ид="' . $v . '"]');
            // Поиск подгрупп
            $tmp = $this->getIdGroups($node);
            $tmp = explode(',', $tmp);

            foreach ($tmp as $k2 => $v2) {
                // Поиск товаров подгрупп
                $goods = $this->xml->xpath('Каталог/Товары/Товар/Группы[Ид="' . $v2 . '"]');
                foreach ($goods as $good) {
                    $good->{'Ид'} = $v; // Переносит товар в родителя
                }
            }
            // Удаление подгрупп
            $dom = dom_import_simplexml($node[0]);
            $oldGroups = $dom->getElementsByTagName('Группы')->item(0);
            $tmp = $dom->removeChild($oldGroups);

        }

        // Удаление групп
        foreach ($rules['delete'] as $k => $v) {
            $node = $this->groupsXML->xpath('//Группа[Ид="' . $k . '"]');
            $dom = dom_import_simplexml($node[0]);
            $dom->parentNode->removeChild($dom);

        }
    }

    /**
     * Возращает строку с ID всех подгрупп через ','
     * @param $node
     * @return string
     */
    private function getIdGroups($node)
    {
        $tmp = '';
        foreach ($node as $k => $v) {
            $tmp .= $v->{'Ид'} . ',';
            $elem = $v->{'Группы'};
            if ($elem->count() != 0) {
                $tmp .= $this->getIdGroups($v->xpath('Группы/Группа')) . ',';
            }
        }
        return substr($tmp, 0, strlen($tmp) - 1);;
    }


}