<?php

// Новости
return array(
    'params' => array (
        'in_structures' => array('Ideal_DataList'), // в каких структурах можно создавать эту структуру
        'elements_cms'  => 20,            // количество элементов в списке в CMS
        'elements_site' => 15,            // количество элементов в списке на сайте
        'field_name'    => '',            // поле для входа в список потомков
        'field_sort'    => 'date_create DESC', // поле, по которому проводится сортировка в CMS
        'field_list'    => array('name', 'is_active', 'date_create', 'url')
     ),
    'fields'   => array (
        'ID' => array(
            'label' => 'Номер заказа',
            'sql'   => 'int(4) unsigned not null auto_increment primary key',
            'type'  => 'Ideal_Hidden'
        ),
        'prev_structure' => array(
            'label' => 'ID родительских структур',
            'sql'   => 'char(15)',
            'type'  => 'Ideal_Hidden'
        ),
        'name' => array(
            'label' => 'Название',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_Text'
        ),
        'url' => array(
            'label' => 'URL',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_UrlAuto'
        ),
        'price' => array(
            'label' => 'Сумма заказа',
            'sql'   => 'int',
            'type'  => 'Ideal_Price'
        ),
        'stock' => array(
            'label' => 'Количество',
            'sql'   => 'int',
            'type'  => 'Ideal_Text'
        ),
        'address'=> array(
            'label' => 'Адрес доставки',
            'sql'   => 'varchar(250)',
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
            'tab'   => 'Заказ',
            'label' => 'Заказ',
            'sql'   => 'mediumtext',
            'type'  => 'Ideal_RichEdit'
        ),
        'is_active' => array(
            'label' => 'Необработанный заказ',
            'sql'   => 'bool',
            'type'  => 'Ideal_Checkbox'
        ),
        'export' => array(
            'label' => 'Попадает в выгрузку 1с',
            'sql'   => 'bool',
            'type'  => 'Ideal_Checkbox'
        ),
    ),
);
