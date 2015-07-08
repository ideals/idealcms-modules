<?php
namespace Shop\Structure\Service\Load1c_v2;

use Ideal\Field\Cid\Model;
use Ideal\Core\Config;

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
    protected $tmp = array();
    /** @var  DbCategory */
    protected $dbCategory;
    /** @var  XmlCategory */
    protected $xmlCategory;
    protected $lastCid;

    public function __construct($dbCategory, $xmlCategory)
    {
        $this->dbCategory = $dbCategory;
        $this->xmlCategory = $xmlCategory;
    }


    public function parse()
    {
        $config = Config::getInstance();
        $part = $config->getStructureByName('Ideal_Part');
        $cid = new Model($part['params']['levels'], $part['params']['digits']);

        // Забираем реззультаты категорий из БД 1m
        $dbResult = $this->dbCategory->parse();

        // Забираем результаты категорий из xml 1m
        $xmlResult = $this->xmlCategory->parse();

        // пройти по выгрузке бд и вставить в хмл данные из бд с ис эктив = 0
        foreach ($dbResult['ids'] as $key => $element) {
            // если в БД not-1c - вставляем элемент к его предку
            if ($element['id_1c'] == 'not-1c') {
                $parentCid = $cid->getCidByLevel($element['cid'], $element['lvl'] - 1, false);
                $parent = $dbResult['cid'][$parentCid]['id_1c'];
                $data = array(
                    'parent' => $parent,
                    'is_active' => $element['is_active'],
                    'pos' => $element['cid'],
                    'Ид' => $element['id_1c']
                );
                if ($parent == null) {
                    $data['Наименование'] = $element['name'];
                }
                $this->xmlCategory->addChild($data);
            } else {
                if (isset($xmlResult[$key])) {
                    // добавляем поля информацию из бд в хмл
                    $data = array(
                        'is_active' => $element['is_active'],
                        'pos' => $element['cid'],
                        'Ид' => $element['id_1c']
                    );
                    $this->xmlCategory->updateElement($data);
                } else {
                    $parentCid = $cid->getCidByLevel($element['cid'], $element['lvl'] - 1, false);
                    $parent = $dbResult['cid'][$parentCid]['id_1c'];
                    $data = array(
                        'parent' => $parent,
                        'is_active' => $element['is_active'],
                        'pos' => $element['cid'],
                        'Ид' => $element['id_1c']
                    );
                    if ($parent == null) {
                        $data['Наименование'] = $element['name'];
                    }
                    $this->xmlCategory->addChild($data);
                }
            }
            // получаем элемент по ид_1с
            // если ничего не получили - добавляем к предку. Если получили - прописываем его is_active и pos и id
            // add child по родителям в xml
        }
exit;
        // сплющиваем хмл вложенность с проставлением Cid, lvl
        // сравниваем сплющенный и массив из БД и находим delete update add







        // Пересечение xml и БД выгрузки - на обновление
        $this->result['update'] = array_intersect_key($this->tmp['xmlResult'], $this->tmp['dbResult']['ids']);

        // Разница между xml->БД выгрузками - на добавление
        $this->result['add'] = array_diff_key($this->tmp['xmlResult'], $this->tmp['dbResult']['ids']);

        // обновляем данные
        $this->update();
        // вставляем в хмл
        $result = $this->insert();
    }

    public function answer()
    {
        return $this->answer;
    }

    public function getData()
    {
        return $this->result;
    }

    protected function insert()
    {
        $this->xmlCategory->delete($this->result['delete']);
        $this->xmlCategory->update($this->result['update']);
        $this->xmlCategory->add($this->result['add']);
        return true;
    }

    protected function update()
    {
        $this->getChangedGroupsRecursive($this->result['update']);
    }

    protected function getChangedGroupsRecursive($groups, $parent = array())
    {
        $isUpdateCid = false; // Нужно ли изменять сортировку категорий в соответствии с 1С

        if (!is_array($groups) || (count($groups) == 0)) {
            return array(
                'update' => array(),
                'add' => array()
            );
        }

        $update = $add = array();
        foreach ($groups as $key => $v) {
            $where = 'none';
            $structureField = $this->getStructureFields($parent);
            if (isset($this->tmp['dbResult']['ids'][$key])) {
                // Категория товара уже есть в БД сайта
                if ($isUpdateCid) {
                    $self['cid'] = ''; // Определяем cid на основе порядка элементов в 1С
                } else {
                    $self = array_merge($v, $this->tmp['dbResult']['ids'][$key]);
                }
                if ($this->tmp['dbResult']['ids'][$key]['lvl'] != $structureField['lvl']) {
                    $self['old_cid_lvl'] = 'cid=' . $structureField['cid'] . ' lvl=' . $structureField['lvl'];
                    $self = array_merge($self, $structureField);
                    $this->tmp['dbResult']['ids'][$key] = $self;
                }
                if (($this->tmp['dbResult']['ids'][$key]['is_active'] == 0)
                    || ($this->tmp['dbResult']['ids'][$key]['name'] != $v['Наименование'])
                    || ($this->tmp['dbResult']['ids'][$key]['lvl'] != $structureField['lvl'])
                ) {
                    $were = 'update';
                    //$update[] = $self;
                }
            } else {
                $self = array_merge($v, $structureField); // Определяем cid на основе максимального
                $where = 'add';
                //$add[] = $self;
                $this->tmp['dbResult']['ids'][$key] = $self;
            }
            $this->tmp['dbResult']['ids'][$key]['is_exist'] = true;

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
                    $this->tmp['dbResult']['ids'][$key]['is_exist'] = false;
                } else {
                    // Обновляем группу, если в ней есть товары
                    $update[] = $self;
                }
            }
            $this->tmp['dbResult']['ids'][$self['cid']]['count_sale'] = (isset($self['count_sale'])) ? $self['count_sale'] : 0;
            $this->tmp['dbResult']['ids'][$self['cid']]['id_1c'] = $self['Ид'];
            $update = array_merge($update, $result['update']);
            $add = array_merge($add, $result['add']);
        }

        return array(
            'update' => $update,
            'add' => $add
        );
    }

    protected function getStructureFields($parent)
    {
        $fields = array(
            'cid' => ''
        );

        if (!isset($parent['lvl'])) {
            // Если уровень предка не указан — ниициализируем
            $parent['lvl'] = 0;
        }

        if (!isset($parent['cid'])) {
            // Если уровень предка не указан — инициализируем
            $parent['cid'] = '';
        }

        $config = Config::getInstance();
        $part = $config->getStructureByName('Ideal_Part');
        $cid = new Model($part['params']['levels'], $part['params']['digits']);

        // Ищем максимальный cid
        $maxCid = $parent['cid'];
        $lvl = $parent['lvl'] + 1;
        foreach ($this->result['dbResult']['ids'] as $v) {
            if ($v['lvl'] != $lvl) {
                continue;
            }
            $vCidSegment = $cid->getCidByLevel($v['cid'], $lvl - 1, false); //
            $parentCidSegment = $cid->getCidByLevel($parent['cid'], $lvl - 1, false); //
            if (($parent['cid'] != '') && ($parentCidSegment != $vCidSegment)) {
                continue;
            }
            if ($v['cid'] > $maxCid) {
                $maxCid = $v['cid'];
            }
        }

        // Прибавляем единицу к максимальному cid
        $fields['cid'] = $cid->setBlock($maxCid, $lvl, '+1', true);
        $fields['lvl'] = $lvl;

        return $fields;
    }
}
