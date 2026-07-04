<?php

// Таблица пользователей
return [
    'params' => [
        'in_structures' => ['Ideal_Part'], // в каких структурах можно создавать эту структуру
        'elements_cms' => 10, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_name' => 'name', // поля для вывода информации по объекту
        'field_sort' => 'pos', // поле, по которому проводится сортировка в CMS
        'field_list' => ['ID', 'name', 'pos', 'url'],
        'levels' => 6, // количество уровней вложенности
        'digits' => 3, // //кол-во разрядов
    ],
    'fields'   =>  [
        'ID' => [
            'label' => 'Идентификатор',
            'sql' => 'int(4) unsigned not null auto_increment primary key',
            'type' => 'Ideal_Hidden',
        ],
        'prev_structure' => [
            'label' => 'ID родительских структур',
            'sql' => 'char(15)',
            'type' => 'Ideal_Hidden',
        ],
        'addon' => [
            'label' => 'Аддоны',
            'sql' => "varchar(255) not null default '[[\"1\",\"Ideal_Page\",\"\"]]'",
            'type' => 'Ideal_Addon',
            'medium'    => '\\Ideal\\Medium\\TemplateList\\Model',
            'available' =>  ['Ideal_Page', 'Ideal_PhpFile', 'Ideal_Photo', 'Ideal_SiteMap', 'Ideal_YandexSearch'],
            'default'   => '[["1","Ideal_Page",""]]',
        ],
        'name' => [
            'label' => 'Заголовок',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_Text',
        ],
        'url' => [
            'label' => 'URL',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_UrlAuto',
        ],
        'pos' => [
            'label' => 'Позиция отображения',
            'sql' => 'int(11) default 1',
            'type' => 'Ideal_Integer',
        ],
        'template' => [
            'label' => 'Шаблон отображения',
            'sql' => "varchar(255) default 'index.twig'",
            'type' => 'Ideal_Select',
            'medium' => '\\Shop\\Medium\\BasketTabsList\\Model',
        ],
        'annot' => [
            'label' => 'Аннотация',
            'sql' => 'text',
            'type' => 'Ideal_Area',
        ],
        'date_create' => [
            'label' => 'Дата создания',
            'sql' => 'int(11) not null',
            'type' => 'Ideal_DateSet',
        ],
        'is_active' => [
            'label' => 'Отображать на сайте',
            'sql' => 'bool',
            'type' => 'Ideal_Checkbox',
        ],
    ],
];
