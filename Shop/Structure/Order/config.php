<?php

// Новости
return [
    'params' =>  [
        'in_structures' => ['Ideal_DataList'], // в каких структурах можно создавать эту структуру
        'structures'    => ['Shop_OrderDetail'], // типы, которые можно создавать в этом разделе
        'elements_cms'  => 20,            // количество элементов в списке в CMS
        'elements_site' => 15,            // количество элементов в списке на сайте
        'field_name'    => 'name',            // поле для входа в список потомков
        'field_sort'    => 'date_create DESC', // поле, по которому проводится сортировка в CMS
        'field_list'    => ['name', 'is_active', 'date_create', 'url'],
    ],
    'fields'   =>  [
        'ID' => [
            'label' => 'Номер заказа',
            'sql'   => 'int(4) unsigned not null auto_increment primary key',
            'type'  => 'Ideal_Hidden',
        ],
        'prev_structure' => [
            'label' => 'ID родительских структур',
            'sql'   => 'char(15)',
            'type'  => 'Ideal_Hidden',
        ],
        'name' => [
            'label' => 'Название',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_Text',
        ],
        'url' => [
            'label' => 'URL',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_UrlAuto',
        ],
        'price' => [
            'label' => 'Сумма заказа',
            'sql'   => 'int',
            'type'  => 'Ideal_Price',
        ],
        'stock' => [
            'label' => 'Количество',
            'sql'   => 'int',
            'type'  => 'Ideal_Text',
        ],
        'address' => [
            'label' => 'Адрес доставки',
            'sql'   => 'varchar(250)',
            'type'  => 'Ideal_Text',
        ],
        'date_create' => [
            'label' => 'Дата создания',
            'sql'   => 'int(11) not null',
            'type'  => 'Ideal_DateSet',
        ],
        'date_mod' => [
            'label' => 'Дата модификации',
            'sql'   => 'int(11) not null',
            'type'  => 'Ideal_DateAuto',
        ],
        'content' => [
            'tab'   => 'Заказ',
            'label' => 'Заказ',
            'sql'   => 'mediumtext',
            'type'  => 'Ideal_RichEdit',
        ],
        'is_active' => [
            'label' => 'Необработанный заказ',
            'sql'   => 'bool',
            'type'  => 'Ideal_Checkbox',
        ],
        'goods_id' => [
            'label' => '1С идентификаторы заказанных товаров',
            'sql' => "text",
            'type' => 'Ideal_Hidden',
        ],
        'export' => [
            'label' => 'Попадает в выгрузку 1с',
            'sql'   => 'bool',
            'type'  => 'Ideal_Checkbox',
        ],
        'structure' => [
            'label' => 'Структура для отображения деталей заказов',
            'sql'   => 'varchar(30) not null',
            'type'  => 'Ideal_Text',
        ],
        'user_id' => [
            'label' => 'Идентификатор пользователя совершившего заказ',
            'sql' => "int(11) DEFAULT '0' NOT NULL",
            'type' => 'Ideal_Hidden',
        ],
    ],
];
