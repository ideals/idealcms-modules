<?php

// Таблица пользователей
return [
    'params' => [
        'structures'    => ['Ideal_User'], // типы, которые можно создавать в этом разделе
        'elements_cms'  => 50, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_sort'    => 'reg_date', // поле, по которому проводится сортировка в CMS
        'field_name'    => '', // поле для входа в список потомков
        'field_list'    => ['email', 'fio', 'reg_date', 'last_visit', 'is_active'],
    ],
    'fields'   =>  [
        'ID' => [
            'label' => 'ID',
            'sql'   => 'int(8) unsigned NOT NULL auto_increment primary key',
            'type'  => 'Ideal_Hidden',
        ],
        'prev_structure' => [
            'label' => 'ID родительских структур',
            'sql'   => 'char(15)',
            'type'  => 'Ideal_Hidden',
        ],
        'email' => [
            'label' => 'E-mail',
            'sql'   => 'varchar(128) NOT NULL',
            'type'  => 'Ideal_Text',
        ],
        'comment' => [
            'label' => 'Комментарий',
            'sql'   => 'text',
            'type'  => 'Ideal_Text',
        ],
        'password' => [
            'label' => 'Пароль',
            'sql'   => 'varchar(100) NOT NULL',
            'type'  => 'Ideal_Password',
        ],
        'reg_date' => [
            'label' => 'Дата регистрации',
            'sql'   => "int(11) DEFAULT '0' NOT NULL",
            'type'  => 'Ideal_DateSet',
        ],
        'last_visit' => [
            'label' => 'Последний вход',
            'sql'   => "int(11) DEFAULT '0' NOT NULL",
            'type'  => 'Ideal_DateSet',
        ],
        'act_key' => [
            'label' => 'Ключ активации',
            'sql'   => 'varchar(32)',
            'type'  => 'Ideal_Hidden',
        ],
        'fio' => [
            'label' => 'ФИО',
            'sql'   => 'varchar(250)',
            'type'  => 'Ideal_Text',
        ],
        'phone' => [
            'label' => 'Телефон',
            'sql'   => 'varchar(250)',
            'type'  => 'Ideal_Text',
        ],
        'city' => [
            'label' => 'Город',
            'sql'   => 'varchar(250)',
            'type'  => 'Ideal_Text',
        ],
        'postcode' => [
            'label' => 'Индекс',
            'sql'   => 'varchar(250)',
            'type'  => 'Ideal_Text',
        ],
        'address' => [
            'label' => 'Адрес',
            'sql'   => 'text',
            'type'  => 'Ideal_Text',
        ],
        'is_active' => [
            'label' => 'Активирован',
            'sql'   => "bool not null default '0'",
            'type'  => 'Ideal_Checkbox',
        ],
        'basket' => [
            'label' => 'Состояние корзины пользователя',
            'sql' => 'text',
            'type' => 'Cabinet_SerializeHidden',
        ],
    ],
];
