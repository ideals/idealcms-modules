<?php
namespace Shop\Structure\Service\Load1c_v2;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:34
 */

class NewCategory
{
    protected $result = array();
    protected $answer = array();

    /**
     * @param DbCategory $dbCategory
     * @param XmlCategory $xmlCategory
     */
    public function parse($dbCategory, $xmlCategory)
    {
        $dbResult = $dbCategory->parse();
        $xmlResult = $xmlCategory->parse();
        $this->getLoadGroups();
        $result = array();
        $this->result = $result;
    }

    public function getData()
    {
        return $this->result;
    }

    protected function getLoadGroups()
    {
        // для каждого удаленного дочернего элемента проверить не изменился ли у родителя cid
        // (до тех пор, пока не найдется)
        // Прописать количество товара в соответствующих категориях
        // Обойти все категории и прорисать количество товара у родительских категорий (в которых нет товара, но есть
        // категории с товаром)

        $result = $this->getChangedGroupsRecursive($this->groups);

        $result['delete'] = array();
        if ($this->status == 'full') {
            // Если грузим полный прайс, то удаляем группы, которые есть в БД, но нет в xml
            $delete = array();
            foreach ($this->oldGroups as $v) {
                if (!$v['is_exist'] && ($v['is_active'] == 1)) {
                    $delete[] = $v;
                }
            }
            $result['delete'] = $delete;
        }

        return $result;

    }

    protected function getChangedGroupsRecursive($groups, $parent = array())
    {
        $isUpdateCid = false; // Нужно ли изменять сортировку категорий в соответствии с 1С

        if (!is_array($groups) || (count($groups) == 0)) {
            // Если категорий у родительского элемента нет, возвращаем пустые массивы
            return array(
                'update' => array(),
                'add' => array()
            );
        }

        $update = $add = array();
        foreach ($groups as $v) {
            $id_1c = $v['Ид'];
            $self = $v;
            // Прописываем кол-во товара в тех группах, в которых он есть
            $self['num'] = (isset($this->goodGroups[$id_1c])) ? count($this->goodGroups[$id_1c]) : 0;
            unset($self['Группы']);

            $where = 'none';
            $structureField = $this->getStructureFields($parent);
            if (isset($this->oldGroups[$id_1c])) {
                // Категория товара уже есть в БД сайта
                if ($isUpdateCid) {
                    $self['cid'] = ''; // Определяем cid на основе порядка элементов в 1С
                } else {
                    $self = array_merge($self, $this->oldGroups[$id_1c]);
                }
                if ($this->oldGroups[$id_1c]['lvl'] != $structureField['lvl']) {
                    $self['old_cid_lvl'] = 'cid=' . $structureField['cid'] . ' lvl=' . $structureField['lvl'];
                    $self = array_merge($self, $structureField);
                    $this->oldGroups[$id_1c] = $self;
                }
                if (($this->oldGroups[$id_1c]['is_active'] == 0)
                    || ($this->oldGroups[$id_1c]['name'] != $v['Наименование'])
                    || ($this->oldGroups[$id_1c]['lvl'] != $structureField['lvl'])
                ) {
                    $were = 'update';
                    //$update[] = $self;
                }
            } else {
                $self = array_merge($self, $structureField); // Определяем cid на основе максимального
                $where = 'add';
                //$add[] = $self;
                $this->oldGroups[$id_1c] = $self;
            }
            $this->oldGroups[$id_1c]['is_exist'] = true;

            $result = $this->getChangedGroupsRecursive($v['Группы'], $self);
            // Считаем кол-во товаров в подкатегориях update и add
            foreach ($result as $typeResult) {
                foreach ($typeResult as $group) {
                    $self['num'] += $group['num'];
                }
            }
            if ($where == 'add' /*AND $self['num'] != 0*/) {
                // Добавляем группу, если в ней есть товары
                $add[] = $self;
            }
            if ($where == 'update') {
                if ($self['num'] == 0) {
                    // Если товаров нет — скрываем группу
                    $this->oldGroups[$id_1c]['is_exist'] = false;
                } else {
                    // Обновляем группу, если в ней есть товары
                    $update[] = $self;
                }
            }
            $this->groupArr[$self['cid']]['count_sale'] = (isset($self['count_sale'])) ? $self['count_sale'] : 0;
            $this->groupArr[$self['cid']]['id_1c'] = $self['Ид'];
            $update = array_merge($update, $result['update']);
            $add = array_merge($add, $result['add']);
        }

        return array(
            'update' => $update,
            'add' => $add
        );
    }

    public function answer()
    {
        return $this->answer;
    }
}
