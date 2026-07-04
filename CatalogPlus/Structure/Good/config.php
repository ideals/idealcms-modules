<?php

// Новости
return [
    'params' =>  [
        'in_structures' => ['Ideal_Part'], // в каких структурах можно создавать эту структуру
        'elements_cms'  => 10,            // количество элементов в списке в CMS
        'elements_site' => 15,            // количество элементов в списке на сайте
        'field_name'    => '',            // поле для входа в список потомков
        'field_sort'    => 'name ASC', // поле, по которому проводится сортировка в CMS
        'field_list'    => ['name', 'price', 'url', 'is_active', 'date_create'],
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
        'category_id' => [
            'label' => 'Категория',
            'sql'   => 'int(11)',
            'type'  => 'CatalogPlus_Category',
            'medium' => '\\CatalogPlus\\Medium\\CategoryList\\Model',
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
        'price' => [
            'label' => 'Цена за единицу',
            'sql'   => 'int',
            'type'  => 'Ideal_Price',
        ],
        'annot' => [
            'label' => 'Описание',
            'sql'   => 'text',
            'type'  => 'Ideal_Area',
        ],
        'template' => [
            'label' => 'Шаблон отображения',
            'sql' => "varchar(255) default 'index.twig'",
            'type' => 'Ideal_Template',
            'medium' => '\\Ideal\\Medium\\TemplateList\\Model',
            'default'   => 'index.twig',
        ],
        'sell' => [
            'tab'   => 'Данные',
            'label' => 'Скидка',
            'sql'   => "int(11) null default '0'",
            'default' => 0,
            'type'  => 'Ideal_Integer',
        ],
        'sell_date' => [
            'tab'   => 'Данные',
            'label' => 'Дата действия скидки',
            'sql'   => 'int(11) null',
            'type'  => 'Ideal_DateAuto',
        ],
        'stock' => [
            'tab'   => 'Данные',
            'label' => 'Кол-во',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Text',
        ],
        'currency' => [
            'tab'   => 'Данные',
            'label' => 'Валюта',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Select',
            'values' => [
                'руб' => 'руб',
                'usd' => 'usd',
            ],
        ],
        'coefficient' => [
            'tab'   => 'Данные',
            'label' => 'Item',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Text',
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
        'is_active' => [
            'label' => 'Отображать на сайте',
            'sql'   => 'bool',
            'type'  => 'Ideal_Checkbox',
        ],
        'is_1c_exchanged' => [
            'label' => 'Выгружено из 1С',
            'sql'   => "bool DEFAULT '0' NOT NULL",
            'type'  => 'Ideal_Checkbox',
        ],
        'is_1c_price_exchanged' => [
            'label' => 'Цена выгружена из 1С',
            'sql'   => "bool DEFAULT '0' NOT NULL",
            'type'  => 'Ideal_Checkbox',
        ],
    ],
];
