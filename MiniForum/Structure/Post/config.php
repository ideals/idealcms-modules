<?php

// Форум
return [
    'params' =>  [
        'in_structures' => ['Ideal_Part', 'MiniForum_Post'], // в каких структурах можно создавать эту структуру
        'elements_cms'      => 10,                      // количество элементов в списке в CMS
        'elements_site'     => 10,                      // количество элементов в списке на сайте
        'field_sort'        => 'date_create DESC',      // поле, по которому проводится сортировка в CMS
        'field_name'        => 'content',               // поля для вывода информации по объекту
        'field_list'        => ['author', 'email', 'date_create', 'is_active'],
    ],
    'fields'   =>  [
        'ID' => [
            'label' => 'Идентификатор',
            'sql'   => 'int(4) unsigned not null auto_increment primary key',
            'type'  => 'Ideal_Hidden',
        ],
        'main_parent_id' => [
            'label' => 'Идентификатор главного сообщения(названия темы)',
            'sql'   => 'int(4) unsigned',
            'type'  => 'Ideal_Hidden',
        ],
        'page_structure' => [
            'label' => 'С какой страницы было отправлено сообщение',
            'sql'   => 'varchar(15)',
            'type'  => 'Ideal_Hidden',
        ],
        'prev_structure' => [
            'label' => 'ID родительских структур',
            'sql'   => 'char(15)',
            'type'  => 'Ideal_Hidden',
        ],
        'parent_id' => [
            'label' => 'Родительское сообщение',
            'sql'   => 'int(11) default 0',
            'type'  => 'Ideal_Text',
        ],
        'author' => [
            'label' => 'Автор',
            'sql'   => 'varchar(100)',
            'type'  => 'Ideal_Text',
        ],
        'email' => [
            'label' => 'E-mail',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Text',
        ],
        'content' => [
            'label' => 'Сообщение',
            'sql'   => 'text not null',
            'type'  => 'Ideal_Area',
        ],
        'referer' => [
            'label' => 'Источник перехода',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_Referer',
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
        'is_moderated' => [
            'label' => 'Прошло модерацию',
            'sql'   => 'bool',
            'type'  => 'Ideal_Checkbox',
        ],
        'is_mail' => [
            'label' => 'Подписка на обновление темы',
            'sql'   => 'bool',
            'type'  => 'Ideal_Checkbox',
        ],
    ],
];
