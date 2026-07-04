<?php

// Новости
return [
    'params' =>  [
        'in_structures' => ['Ideal_Part'], // в каких структурах можно создавать эту структуру
        'elements_cms'  => 30,            // количество элементов в списке в CMS
        'elements_site' => 30,            // количество элементов в списке на сайте
        'field_name'    => '',            // поле для входа в список потомков
        'field_sort'    => 'pos ASC',    // поле, по которому проводится сортировка в CMS
        'field_list'    => ['pos', 'name', 'is_active', 'date_create'],
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
        'pos' => [
            'label' => '№',
            'sql'   => 'int not null',
            'type'  => 'Ideal_Pos',
        ],
        'name' => [
            'label' => 'Название',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_Text',
        ],
        'cat' => [
            'label' => 'Название галереи',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Text',
        ],
        'dir_img' => [
            'label' => 'Директория с фото',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Text',
        ],
        'info' => [
            'label' => 'Аннотация к фото',
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
    ],
];
