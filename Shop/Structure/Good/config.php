<?php

// Новости
return array(
    'params' => array (
        'in_structures'    => array('Shop_Category'), // в каких структурах можно создавать эту структуру
        'elements_cms'  => 10,            // количество элементов в списке в CMS
        'elements_site' => 15,            // количество элементов в списке на сайте
        'field_cap'     => '',            // поле для входа в список потомков
        'field_sort'    => 'name ASC', // поле, по которому проводится сортировка в CMS
        'field_list'    => array('name', 'is_active', 'date_create')
     ),
    'fields'   => array (
        'ID' => array(
            'label' => 'Идентификатор',
            'sql'   => 'int(4) unsigned not null auto_increment primary key',
            'type'  => 'Ideal_Hidden'
        ),
        'id_1c' => array(
            'label' => 'ID в 1С',
            'sql'   => 'char(37)',
            'type'  => 'Ideal_Text'
        ),
        'structure_path' => array(
            'label' => 'ID родительских структур',
            'sql'   => 'char(15)',
            'type'  => 'Ideal_Hidden'
        ),
        'category' => array(
            'label' => 'Категория',
            'sql'   => '',
            'type'  => 'Shop_Category',
            'class' => '\\Shop\\Structure\\Category\\Getters\\CategoryList'
        ),
        'name' => array(
            'label' => 'Название',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_Text'
        ),
        'url' => array(
            'label' => 'URL',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_UrlAuto'
        ),
        'price' => array(
            'label' => 'Цена за единицу',
            'sql'   => 'int',
            'type'  => 'Shop_Price'
        ),
        'stock' => array(
            'label' => 'Количество',
            'sql'   => 'int',
            'type'  => 'Ideal_Text'
        ),
        'currency'=> array(
            'label' => 'Валюта',
            'sql'   => 'char(5)',
            'type'  => 'Ideal_Text'
        ),
        'item'=> array(
            'label' => 'Единица измерения',
            'sql'   => 'varchar(10)',
            'type'  => 'Ideal_Text'
        ),
        'coefficient'=> array(
            'label' => 'Коэффициент',
            'sql'   => 'float',
            'type'  => 'Ideal_Text'
        ),
        'measure'=> array(
            'label' => 'Базовая единица',
            'sql'   => 'varchar(50)',
            'type'  => 'Ideal_Text'
        ),
        'height'=> array(
            'label' => 'Высота',
            'sql'   => 'varchar(50)',
            'type'  => 'Ideal_Text'
        ),
        'diameter'=> array(
            'label' => 'Диаметр',
            'sql'   => 'varchar(50)',
            'type'  => 'Ideal_Text'
        ),
        'general'=> array(
            'label' => 'Основное свойство',
            'sql'   => 'varchar(50)',
            'type'  => 'Ideal_Text'
        ),
        'cell'=> array(
            'label' => 'Размер ячейки',
            'sql'   => 'varchar(50)',
            'type'  => 'Ideal_Text'
        ),
        'width'=> array(
            'label' => 'Высота',
            'sql'   => 'varchar(50)',
            'type'  => 'Ideal_Text'
        ),
        'steel'=> array(
            'label' => 'Марка стали',
            'sql'   => 'varchar(50)',
            'type'  => 'Ideal_Text'
        ),
        'length'=> array(
            'label' => 'Длина',
            'sql'   => 'varchar(50)',
            'type'  => 'Ideal_Text'
        ),
        'color'=> array(
            'label' => 'Цвет',
            'sql'   => 'varchar(50)',
            'type'  => 'Ideal_Text'
        ),
        'rib'=> array(
            'label' => 'Количесто рёбер жесткости',
            'sql'   => 'varchar(50)',
            'type'  => 'Ideal_Text'
        ),
        'full_name'=> array(
            'label' => 'Полное наименование',
            'sql'   => 'varchar(250)',
            'type'  => 'Ideal_Text'
        ),
        'weight'=> array(
            'label' => 'Вес',
            'sql'   => 'varchar(50)',
            'type'  => 'Ideal_Text'
        ),
        'date_create' => array(
            'label' => 'Дата создания',
            'sql'   => 'int(11) not null',
            'type'  => 'Ideal_DateSet'
        ),
        'date_mod' => array(
            'label' => 'Дата модификации',
            'sql'   => 'int(11) not null',
            'type'  => 'Ideal_DateAuto'
        ),
        'content' => array(
            'label' => 'Сообщение',
            'sql'   => 'text',
            'type'  => 'Ideal_Area' // fullblock
        ),
        'is_active' => array(
            'label' => 'Отображать на сайте',
            'sql'   => 'bool',
            'type'  => 'Ideal_Checkbox'
        ),
    ),
);
