<?php

// Новости
return [
    'params' =>  [
        'in_structures' => ['Ideal_Part'], // в каких структурах можно создавать эту структуру
        'elements_cms'  => 10,            // количество элементов в списке в CMS
        'elements_site' => 16,            // количество элементов в списке на сайте
        'field_name'    => '',            // поле для входа в список потомков
        'field_sort'    => 'name', // поле, по которому проводится сортировка в CMS
        'field_list'    => ['name', 'is_active', 'date_create'],
    ],
    'fields'   =>  [
        'ID' => [
            'label' => 'Идентификатор',
            'sql'   => 'int(4) unsigned not null auto_increment primary key',
            'type'  => 'Ideal_Hidden',
        ],
        'prev_structure' => [
            'label' => 'ID родительских структур',
            'sql'   => 'char(15)',
            'type'  => 'Ideal_Hidden',
        ],
        'name' => [
            'label' => 'Заголовок',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_Text',
        ],
        'url' => [
            'label' => 'URL',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_UrlAuto',
        ],
        'img' => [
            'label' => 'Картинка',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Image',
        ],
        'annot' => [
            'label' => 'Аннотация',
            'sql'   => 'text',
            'type'  => 'Ideal_Area',
        ],
        'date_create' => [
            'label' => 'Дата создания',
            'sql'   => 'int(11) not null',
            'type'  => 'Ideal_DateSet',
        ],
        'is_active' => [
            'label' => 'Отображать на сайте',
            'sql'   => 'bool',
            'type'  => 'Ideal_Checkbox',
        ],
        'title' => [
            'tab'   => 'SEO',
            'label' => 'Title',
            'sql'   => 'text',
            'type'  => 'Ideal_Area',
        ],
        'keywords' => [
            'tab'   => 'SEO',
            'label' => 'Keywords tag',
            'sql'   => 'text',
            'type'  => 'Ideal_Area',
        ],
        'description' => [
            'tab'   => 'SEO',
            'label' => 'Description tag',
            'sql'   => 'text',
            'type'  => 'Ideal_Area',
        ],
    ],
];
