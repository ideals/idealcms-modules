<?php
namespace Shop\Structure\Service\Load1c;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Field\Cid;

class Model
{
    // Основной xml-контент, загруженный из файла
    protected $xml;
    // Категории товара
    protected $groups;
    // Свойства товара
    protected $props;
    // Принадлежность каждого товара к определённой группе
    protected $goodGroups;
    // full | update — выгружается весь каталог или только изменения
    public $status;
    protected $oldGroups;


    /**
     * Загрузка в память групп, свойств и реквизитов товара
     * Выяснение статуса загрузки товаров - полный каталог или только изменения
     * @param string $importFile Путь к файлу import.xml
     * @param string $offersFile Путь к файлу offers.xml
     * @param string $idTypeOfPrice Ид типа цены для сайта
     */
    public function __construct($importFile, $offersFile, $idTypeOfPrice)
    {
        $this->xml = simplexml_load_file($importFile);

        // Считываем категории товара в массив $this->groups
        $groupsXML = $this->xml->xpath('Классификатор/Группы');
        $this->groups = $this->loadGroups($groupsXML[0]);
        unset($groupsXML);

        // Считываем свойства товара в массив $this->props
        $props = array();
        $propsXML = $this->xml->xpath('Классификатор/Свойства/Свойство');
        foreach ($propsXML as $child) {
            $id = (string)$child->{'Ид'};
            $props[$id] = (string)$child->{'Наименование'};
        }
        $this->props = $props;

        // Считываем цену и количество товара
        $xml = simplexml_load_file($offersFile);
        $goodsXML = $xml->xpath('ПакетПредложений/Предложения');
        $this->offers = $this->getOffers($goodsXML[0], $idTypeOfPrice);

        // Определяем весь каталог выгружается, или только изменения
        $attributes = $xml->{'ПакетПредложений'}->attributes();
        if ($attributes['СодержитТолькоИзменения'] == 'false') {
            $this->status = 'full';
        } else {
            $this->status = 'update';
        }
    }


    /**
     * Создания массива категории для сайта
     * id-1c ID взятый из выгрузки 1с
     * id-site ID категорий на сайте
     * @param bool $list
     * @param bool $group
     * @param null $IDroot
     * @return array
     */
    protected function loadSiteGroup($list = false, $group = false, $IDroot = null)
    {
        $arr = array();
        if (!$list) {
            $db = Db::getInstance();
            $table = ''; //Таблица где хронятся соотношений id-site к id-1c категорий
            $_sql = "SELECT * FROM {$table}";
            $list = $db->queryArray($_sql);
            $_tmp = array();
            //Создаем массив где ключ id-1c, а значения соответсвуют id-site
            foreach ($list as $v) {
                $_tmp[$v['id-1c']]['id'] = $v['id-site'];
                $_tmp[$v['id-1c']]['terminal'] = $v['terminal'];
            }
            $list = $_tmp;
            $group = $this->groups;
        }
        foreach ($group as $v) {
            if ($IDroot == null) {
                $ter = $list[$v['Ид']]['terminal'];
                $IDroot = ($ter == 1) ? $list[$v['Ид']]['id'] : null;
            }
            $i = (isset($IDroot)) ? $IDroot : $list[$v['Ид']]['id'];
            $arr[$list[$v['Ид']]] = $i;
            if (count($v['Группы']) > 0) {
                $_tmp = $this->loadSiteGroup($list, $v['Группы'], $IDroot);
                $arr += $_tmp;
            }
        }
        return $arr;
    }

    /**
     * Создание select тега для удобного сопоставления категорий
     * @param $id ID категории из 1c с родителем
     * @return string список для выбора
     */
    private function getGroupSelect($id)
    {
        $mapping = array();
        $IDreal = basename($id); //ID категории в 1c без родителей

        $db = Db::getInstance();
        $query = $db->query("SELECT * FROM i_1c_category");//Загрузка уже созданных сопоставлений
        while ($row = mysql_fetch_array($query, MYSQL_ASSOC)) {
            $mapping[$row['1c_id']] = $row['category_id'];
        }
        $select = ($mapping[$IDreal] == -1) ? 'selected' : '';

        $table = 'i_shop_structure_category';
        $_sql = "SELECT ID,cap FROM {$table} ORDER BY cid ASC";

        $str = '<select name="form[][' . $id . '][v]">';
        $str .= '<option value="0"></option>';
        $str .= '<option ' . $select . ' value="-1">НЕ СОХРАНЯТЬ</option>';
        foreach ($db->queryArray($_sql) as $v) {
            $select = ($mapping[$IDreal] == $v['ID']) ? 'selected' : '';
            $str .= '<option ' . $select . ' value=' . $v['ID'] . '>' . $v['cap'] . '</option>';
        }
        $str .= '</select>';
        return $str;
    }

    /**
     * Создание списка для редактирования сопоставлений категорий
     * @param null $tree Массив категорий из 1с
     * @param string $root Список с отображение названия и сопоставления категрий из 1с к группам на сайте
     * @return string возрашает массив с сопоставление категорий где ключ ID 1с, а значение ID категории на сайте
     */
    public function createTableMapping($tree = null, $root = '')
    {
        $str = ''; // Строка на возвращение

        $goodsXML = $this->xml->xpath('Каталог/Товары/Товар');
        $goodGroups = array();
        foreach ($goodsXML as $child) {
            foreach ($child->{'Группы'}->children() as $item) {
                $id = (string)$item;
                if (isset($goodGroups[$id])) {
                    $goodGroups[$id]++;
                } else {
                    $goodGroups[$id] = 1;
                }
            }
        }
        $this->goodGroups = $goodGroups;

        if ($tree == null) $tree = $this->groups;
        $str .= '<ul>';

        foreach ($tree as $key => $value) {
            $selectID = $root . '/' . $value['Ид'] . ''; //ID родителя для поиска значения в createArrayMapping()
            // Если в категории нету товара то она не отображееться, но подкатегории да, если у них есть товар
            $str .= '<li><label style="min-height:50px">' . $value['Наименование'];
            if (isset($value['Ид']) AND isset($this->goodGroups[$value['Ид']])) {
                $str .= ' (' . $this->goodGroups[$value['Ид']] . ')';
            }
            $str .= '<div style="float:right">' . $this->getGroupSelect($selectID) . '</div></label>';

            if (count($value['Группы']) > 0) {
                $str .= $this->createTableMapping($value['Группы'], $selectID);
            }
            $str .= '</li>';
        }
        $str .= '</ul>';
        return $str;
    }

    /**
     * Создание массива сопоставление и сохранение его в базу
     * @param $data Массив с ключом в виде иерархии /parent/child/child
     * @return array возрашает массив с сопоставление категорий где ключ ID 1с, а значение ID категории на сайте
     */
    public function createArrayMapping($data)
    {
        $arr = array();
        $str = "INSERT INTO i_1c_category (1c_id,category_id) VALUES ";
        foreach ($data as $k => $v) {
            foreach ($v as $k2 => $v2) {
                $bn = basename($k2); // Создание ключа
                if ($v2['v'] == 0) {
                    $root = str_replace('/' . $bn, '', $k2);
                    // Поиск значение у родетеля(ей)
                    while ($root != '') {
                        if ($arr[basename($root)] != 0) {
                            $arr[$bn] = $arr[basename($root)];
                            $str .= "('{$bn}',{$arr[basename($root)]}),";
                            break;
                        } else {
                            $root = str_replace('/' . basename($root), '', $root);
                        }
                    }
                } else {
                    $str .= "('{$bn}',{$v2['v']}),";
                    $arr[$bn] = $v2['v'];
                }
            }
        }
        $str = substr($str, 0, strlen($str) - 1);
        $str .= ' ON DUPLICATE KEY UPDATE category_id = VALUES(category_id);'; //Если строка с id_1c же есть, то обновить значение
        $db = Db::getInstance();
        $db->query($str);
        return $arr;
    }


    /**
     * Превращение групп товаров из SimpleXML объекта в многомерный массив
     * @param $groupsXML Узел с группами
     * @return array Массив с группами
     */
    protected function loadGroups($groupsXML)
    {
        if ($groupsXML->count() == 0) return array();
        $groups = array();
        foreach ($groupsXML->{'Группа'} as $child) {
            $id = (string)$child->{'Ид'};
            $groups[$id] = array(
                'Ид' => $id,
                'Наименование' => (string)$child->{'Наименование'},
                'Группы' => $this->loadGroups($child->{'Группы'})
            );
        }
        return $groups;
    }


    /**
     * Возвращает список свойств товара. Необходимо для первоначальной загрузки товара
     * @return array Массив со списком свойств товара
     */
    public function getProperties()
    {
        return $this->props;
    }


    /**
     * Отображает вложенную структру, где вложеность реализована через поле "Группы"
     * @param $tree Дерево для отображения
     */
    protected function printStructure($tree)
    {
        print '<ul>';
        foreach ($tree as $key => $value) {
            print '<li>' . $value['Наименование'];
            // Для групп выводим кол-во привязанного товара (если есть)
            if (isset($value['Ид']) AND isset($this->goodGroups[$value['Ид']])) {
                print ' (' . $this->goodGroups[$value['Ид']] . ')';
            }
            if (count($value['Группы']) > 0) {
                $this->printStructure($value['Группы']);
            }
            print '</li>';
        }
        print '</ul>';
    }


    /**
     * Распечатка категорий, с указанием сколько в каждой товаров
     * TODO доработать, чтобы товары с нулевой ценой не учитывались
     */
    public function checkGoodsInGroups()
    {
        // Устраиваем цикл по товарам
        $goodsXML = $this->xml->xpath('Каталог/Товары/Товар');
        $goodGroups = array();
        foreach ($goodsXML as $child) {
            foreach ($child->{'Группы'}->children() as $item) {
                $id = (string)$item;
                if (isset($goodGroups[$id])) {
                    $goodGroups[$id]++;
                } else {
                    $goodGroups[$id] = 1;
                }
            }
        }
        $this->goodGroups = $goodGroups;
        $this->printStructure($this->groups);
    }

    /* use */
    protected function getGoodProperties($node, $fields)
    {
        $props = array();
        if ($node->getName() == '') {
            // У этого товара свойства не заданы
            return $props;
        }
        foreach ($node->children() as $item) {
            $itemId = (string)$item->{'Ид'};
            if (!isset($fields[$this->props[$itemId]])) continue;
            $fieldName = $fields[$this->props[$itemId]];
            $props[$fieldName] = (string)$item->{'Значение'};
        }
        return $props;
    }

    /*???*/
    protected function getGoodCharacteristics($node, $fields)
    {
        //print_r($child->{'ХарактеристикиТовара'});
        $good['char'][0]['ID'] = $id;
        $char = $node->{'ХарактеристикиТовара'};
        if ($char != '') {
            // Добавляем характеристики, только в случае, если они есть
            foreach ($char->children() as $item) {
                $itemId = (string)$item->{'Наименование'};
                if (!isset($fields['ХарактеристикиТовара'][$itemId])) continue;
                $fieldName = $fields['ХарактеристикиТовара'][$itemId];
                $good['char'][0][$fieldName] = (string)$item->{'Значение'};
            }
        }

        $article = $good[$fields['Артикул']];
        if (isset($goods[$article])) {
            // Товар с таким артикулом уже есть, добавляем только его хар-ки
            $goods[$article]['char'][] = $good['char'][0];
        } else {
            // Такого артикула нет, просто добавляем товар в массив
            $goods[$article] = $good;
        }
        //exit;
    }

    /* use */
    protected function getGoodRequisites($node, $fields)
    {
        $props = array();
        foreach ($node->children() as $item) {
            $itemId = (string)$item->{'Наименование'};
            if (!isset($fields[$itemId])) continue;
            $fieldName = $fields[$itemId];
            $props[$fieldName] = (string)$item->{'Значение'};
        }
        return $props;
    }


    // Отображение в человекопонятном виде таблицы товаров для анализа
    public function printGoodTable($goods, $fields)
    {
        // Делаем плоский масссив из $fields
        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $fields = array_merge($fields, $value);
                unset($fields[$key]);
            }
        }

        echo '<table>';
        echo '<tr>';
        foreach ($fields as $key => $field) {
            echo '<th>' . $key . '</th>';
        }
        echo '</tr>';

        foreach ($goods as $k => $good) {
            echo '<tr>';
            foreach ($fields as $field) {
                if (!isset($good[$field])) {
                    $good[$field] = '';
                }
                echo '<td>' . $good[$field] . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    }

    /* ??? */
    public function getGoodsToGroups()
    {
        return $this->goodGroups;
    }

    /* ??? */
    public function setOldGroups($groups)
    {
        // В качестве ключей для категорий из БД ставим ключ 1С
        foreach ($groups as $v) {
            $v['is_exist'] = false;
            if ($v['id_1c'] == '') {
                $v['id_1c'] = $v['ID'];
            }
            $oldGroups[$v['id_1c']] = $v;
        }
        $this->oldGroups = $oldGroups;
    }

    /* ??? */
    public function getLoadGroups()
    {
        // Прописать количество товара в соответствующих категориях
        // Обойти все категории и прорисать количество товара у родительских категорий (в которых нет товара, но есть категории с товаром)

        $result = $this->getChangedGroupsRecursive($this->groups);

        $result['delete'] = array();
        if ($this->status == 'full') {
            // Если грузим полный прайс, то удаляем группы, которые есть в БД, но нет в xml
            $delete = array();
            foreach ($this->oldGroups as $v) {
                if (!$v['is_exist'] AND $v['is_active'] == 1) {
                    $delete[] = $v;
                }
            }
            $result['delete'] = $delete;
        }

        return $result;

    }

    /* use */
    protected function getChangedGroupsRecursive($groups, $parent = array())
    {
        $isUpdateCid = false; // Нужно ли изменять сортировку категорий в соответствии с 1С

        if (!is_array($groups) OR count($groups) == 0) {
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
                if ($this->oldGroups[$id_1c]['is_active'] == 0
                    OR $this->oldGroups[$id_1c]['cap'] != $v['Наименование']
                    OR $this->oldGroups[$id_1c]['lvl'] != $structureField['lvl']
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
            if ($where == 'add' AND $self['num'] != 0) {
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
            $update = array_merge($update, $result['update']);
            $add = array_merge($add, $result['add']);
        }

        return array(
            'update' => $update,
            'add' => $add
        );
    }

    /* use */
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
        $cid = new Cid\Model($part['params']['levels'], $part['params']['digits']);

        // Ищем максимальный cid
        $maxCid = $parent['cid'];
        $lvl = $parent['lvl'] + 1;
        foreach ($this->oldGroups as $v) {
            if ($v['lvl'] != $lvl) continue;
            $vCidSegment = $cid->getCidByLevel($v['cid'], $lvl - 1, false); //
            $parentCidSegment = $cid->getCidByLevel($parent['cid'], $lvl - 1, false); //
            if ($parent['cid'] != '' AND $parentCidSegment != $vCidSegment) continue;
            if ($v['cid'] > $maxCid) {
                $maxCid = $v['cid'];
            }
        }

        // Прибавляем единицу к максимальному cid
        $fields['cid'] = $cid->setBlock($maxCid, $lvl, '+1', true);
        $fields['lvl'] = $lvl;

        return $fields;
    }

    /* ??? */
    public function getGoods($fields, $goods)
    {
        // В качестве ключей для категорий из БД ставим ключ 1С
        foreach ($goods as $v) {
            $v['is_exist'] = false;
            if ($v['id_1c'] == '') {
                $v['id_1c'] = $v['ID'];
            }
            $oldGoods[$v['id_1c']] = $v;
        }

        $goodsXML = $this->xml->xpath('Каталог/Товары/Товар');
        $goods = array(
            'add' => array(),
            'update' => array(),
            'delete' => array()
        );
        foreach ($goodsXML as $child) {
            //print_r($child);
            $good = array();

            $id1c = (string)$child->{'Ид'};

            // Заполняем параметры товара
            foreach ($fields as $key => $value) {
                if ($key == 'ЗначенияСвойств') {
                    // Считываем свойства
                    $properties = $this->getGoodProperties($child->{$key}, $fields[$key]);
                    $good = array_merge($good, $properties);
                    continue;
                } elseif ($key == 'ЗначенияРеквизитов') {
                    // Считываем реквизиты
                    $properties = $this->getGoodRequisites($child->{$key}, $fields[$key]);
                    $good = array_merge($good, $properties);
                    continue;
                } elseif (is_array($value)) {
                    // Останавливаемся на массивах без парсера
                    echo 'Массив без парсера: ' . $key;
                    exit;
                }
                // Заполняем информацию о цене и количестве
                if (isset($this->offers[$id1c]) AND isset($this->offers[$id1c][$key])) {
                    $good[$value] = $this->offers[$id1c][$key];
                    //print $value . ' -- ' . $key . ' -- ' . $this->offers[$id1c][$key] . '<br />';
                    continue;
                }
                $good[$value] = (string)$child->$key;
            }

            if (!isset($this->offers[$id1c]) OR $this->offers[$id1c]['ЦенаЗаЕдиницу'] == 0) {
                // В выгрузке цена нулевая, значит товар на сайте отображать не надо
                if (isset($oldGoods[$id1c]) AND ($oldGoods[$id1c]['is_active'] == 1)) {
                    // Если товар есть в БД и его видно на сайте, то добавляем его к списку на удаление
                    $goods['delete'][] = $good;
                }
                // Если в выгрузке у товара нет цены, и такого товара нет в БД, то просто пропускаем его
                continue;
            }

            // Проверяем был ли товар уже в БД сайта
            if (isset($oldGoods[$id1c])) {
                // Товар уже есть в БД сайта - обновляем
                $oldGoods[$id1c]['is_exist'] = true;
                $good['ID'] = $oldGoods[$id1c]['ID'];
                $goods['update'][] = $good;
                // TODO сделать проверку на изменившиеся поля
            } else {
                // Товара нет в БД сайта - добавляем
                $goods['add'][] = $good;
            }

            // Считываем принадлежность товара к группам
            foreach ($child->{'Группы'}->children() as $item) {
                $id = (string)$item;
                $this->goodGroups[$id][] = $id1c;
            }

        }

        if ($this->status == 'full') {
            // Если грузим полный прайс, то удаляем товары, которые есть в БД, но нет в xml
            foreach ($oldGoods as $k => $v) {
                if ($v['is_exist'] == false) {
                    $goods['delete'][] = $v;
                }
            }
        }

        return $goods;
    }

    /* use */
    protected function getOffers($offers, $idTypeOfPrice)
    {
        //print_r($offers); exit;
        //if ($offers->count() == 0) return array();
        $offersArr = array();
        foreach ($offers->{'Предложение'} as $child) {
            $id = (string)$child->{'Ид'};
            foreach ($child->{'Цены'}->{'Цена'} as $price) {
                if ((string)$price->{'ИдТипаЦены'} != $idTypeOfPrice) continue;
                $offersArr[$id] = array(
                    'Количество' => (string)$child->{'Количество'},
                    'ЦенаЗаЕдиницу' => (string)$price->{'ЦенаЗаЕдиницу'},
                    'Валюта' => (string)$price->{'Валюта'},
                    'Единица' => (string)$price->{'Единица'},
                    'Коэффициент' => (string)$price->{'Коэффициент'}
                );

            }
        }
        return $offersArr;
    }

}