<?php
namespace Shop\Structure\Service\Load1c;


class ModGroups
{
    protected $groupsXML;

    public function __construct($groupsXML)
    {
        $this->groupsXML = $groupsXML;

        $rules = array(
            // Правила на создание групп
            'insert' => array(
                /*array(
                    'name' => 'TEST', Имя категории
                    'ID'   => 'insert-1', ID категории
                    'parent' => '', ID одителя
                )*/
                array(
                    'name' => 'Металлоизделия',
                    'ID' => 'qwerty-0001',
                    'parent' => ''
                ),
                array(
                    'name' => 'Металлические заборы',
                    'ID' => 'qwerty-0002',
                    'parent' => 'ceeb24b4-a0d5-11e2-aa72-1c6f65d9c788'
                ),
                array(
                    'name' => 'Сетка для забора, ограждения',
                    'ID' => 'qwerty-0003',
                    'parent' => 'ceeb24b4-a0d5-11e2-aa72-1c6f65d9c788'
                ),
                array(
                    'name' => 'Металлические ограждения',
                    'ID' => 'qwerty-0004',
                    'parent' => 'ceeb24b4-a0d5-11e2-aa72-1c6f65d9c788'
                ),
                array(
                    'name' => 'Сетка пластиковая',
                    'ID' => 'qwerty-0005',
                    'parent' => ''
                ),
                array(
                    'name' => 'Геосетка',
                    'ID' => 'qwerty-0006',
                    'parent' => 'qwerty-0005'
                ),
                array(
                    'name' => 'Малярная сетка',
                    'ID' => 'qwerty-0007',
                    'parent' => 'qwerty-0005'
                ),
                array(
                    'name' => 'Просечная сетка',
                    'ID' => 'qwerty-0008',
                    'parent' => '5bb2d5e8-2318-11dc-b828-001617bd4463'
                ),
                array(
                    'name' => 'Сетка дорожная',
                    'ID' => 'qwerty-0009',
                    'parent' => '28134ade-2318-11dc-b828-001617bd4463'
                ),
                array(
                    'name' => 'Нержавеющая сетка',
                    'ID' => 'qwerty-0010',
                    'parent' => '28134ade-2318-11dc-b828-001617bd4463'
                ),
                array(
                    'name' => 'Cетка оцинкованная',
                    'ID' => 'qwerty-0011',
                    'parent' => '28134ade-2318-11dc-b828-001617bd4463'
                ),
                array(
                    'name' => 'Арматурная сетка',
                    'ID' => 'qwerty-0012',
                    'parent' => '28134ade-2318-11dc-b828-001617bd4463'
                ),
                array(
                    'name' => 'Кладочная сетка',
                    'ID' => 'qwerty-0013',
                    'parent' => '28134ade-2318-11dc-b828-001617bd4463'
                ),
                array(
                    'name' => 'Cетка рабица',
                    'ID' => 'qwerty-0014',
                    'parent' => '28134ade-2318-11dc-b828-001617bd4463'
                ),
                array(
                    'name' => 'Забор из рабицы',
                    'ID' => 'qwerty-0015',
                    'parent' => 'ceeb24b4-a0d5-11e2-aa72-1c6f65d9c788'
                )
            ),
            // Правила на удаление групп
            'delete' => array(
                //'id_1c_element_for_delete' => 1
                'a7e216ca-d4f4-11df-8493-001617a7c060' => 1, // Гидростеклоизол
                // 'ceeb24b4-a0d5-11e2-aa72-1c6f65d9c788' => 1, // СИСТЕМЫ ОГРАЖДЕНИЙ
                // 'c553344c-9dc5-11e2-bfb5-1c6f65d9c788' => 1  // ВНУТРЕННЯЯ (Шпуля, Ограждения/панели/столбы/хомуты и т.д.)

            ),
            // Правила на перемещение групп
            'move' => array(
                /*array(
                    // менять только значения
                    'IDparent' => 'parent_id',
                    'IDchild' => 'child_id'
                ),*/
                array(
                    // Сетки полипропиленовые в Сетка пластиковая
                    'IDparent' => 'qwerty-0005',
                    'IDchild' => '65241e2d-4c0e-11de-87a8-001617a59345'
                ),
                array(
                    // Клетки/кормушки в Металлоизделия
                    'IDparent' => 'qwerty-0001',
                    'IDchild' => '29144868-a267-11e2-b486-1c6f65d9c788'
                ),
                array(
                    // Проволока в Металлопрокат
                    'IDparent' => '54a39fc7-2318-11dc-b828-001617bd4463',
                    'IDchild' => '54a39f26-2318-11dc-b828-001617bd4463'
                ),
                array(
                    // Металлопрокат в Металлоизделия
                    'IDparent' => 'qwerty-0001',
                    'IDchild' => '54a39fc7-2318-11dc-b828-001617bd4463'
                )
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
        }

        // Удаление групп
        foreach ($rules['delete'] as $k => $v) {
            $node = $this->groupsXML->xpath('//Группа[Ид="' . $k . '"]');
            $dom = dom_import_simplexml($node[0]);
            $dom->parentNode->removeChild($dom);

        }
    }


}