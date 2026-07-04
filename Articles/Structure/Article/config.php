<?php

// Новости
return [
    'params' =>  [
        'in_structures' => ['Ideal_Part'], // в каких структурах можно создавать эту структуру
        'elements_cms'  => 20,            // количество элементов в списке в CMS
        'elements_site' => 15,            // количество элементов в списке на сайте
        'field_name'    => '',            // поле для входа в список потомков
        'field_sort'    => 'date_create ASC', // поле, по которому проводится сортировка в CMS
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
        'tag' => [
            'label' => 'Тег',
            'sql'   => '',
            'type'  => 'Ideal_SelectMulti',
            'medium' => '\\Ideal\\Medium\\TagList\\Model',
        ],
        'name' => [
            'label' => 'Название',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_Text',
        ],
        'url' => [
            'label' => 'URL',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_UrlAuto',
        ],
        'annot' => [
            'label' => 'Описание',
            'sql'   => 'text',
            'type'  => 'Ideal_Area',
        ],
        'img' => [
            'label' => 'Картинка',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Image',
        ],
        'date_create' => [
            'tab'   => 'SEO',
            'label' => 'Дата создания',
            'sql'   => 'int(11) not null',
            'type'  => 'Ideal_DateSet',
        ],
        'date_mod' => [
            'tab'   => 'SEO',
            'label' => 'Дата модификации',
            'sql'   => 'int(11) not null',
            'type'  => 'Ideal_DateAuto',
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
        'content' => [
            'label' => 'Текст',
            'sql'   => 'mediumtext',
            'type'  => 'Ideal_RichEdit',
        ],
        'is_active' => [
            'label' => 'Отображать на сайте',
            'sql'   => 'bool',
            'type'  => 'Ideal_Checkbox',
        ],
    ],
];
