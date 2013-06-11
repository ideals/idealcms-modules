<?php
namespace Shop\Structure\Service\Load1c;


class ModGroups {
    public function __construct($groupsXML)
    {
        $this->groupsXML = $groupsXML;

        $rules = array(
            array(
                'type'      => 'delete',
                'ID'        => '', // id удаляемой группы
            ),
            array(
                'type'      => 'insert',
                'ID'        => '', // id, который будет присвоен создаваемой группе
                'parent_id' => ''  // id, в который добавится эта группа
            ),
            array(
                'type'      => 'move',
                'ID'        => '', // id перемещаемой группы
                'parent_id' => ''  // id, в который добавится эта группа
            )
        );

        foreach ($rules as $rule) {
            $action = $rule['type'] . 'action';
            $this->$action();
        }

    }


    protected function deleteAction()
    {

    }


    protected function insertAction()
    {
        $groupsXML1 = $this->groupsXML->xpath("//Группа[Ид='29144868-a267-11e2-b486-1c6f65d9c788']");
        $groupsXML1[0]->addChild('Группы')->addChild('Группа')->addChild('Ид', 123);
    }


    protected function moveAction()
    {

    }
}