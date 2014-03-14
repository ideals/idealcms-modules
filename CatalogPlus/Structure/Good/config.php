<?php

// Новости
return array(
    'params' => array (
        'in_structures'    => array('Ideal_Part'), // в каких структурах можно создавать эту структуру
        'elements_cms'  => 10,            // количество элементов в списке в CMS
        'elements_site' => 15,            // количество элементов в списке на сайте
        'field_name'    => '',            // поле для входа в список потомков
        'field_sort'    => 'name ASC', // поле, по которому проводится сортировка в CMS
        'field_list'    => array('name', 'price', 'url', 'is_active', 'date_create')
     ),
    'fields'   => array (
        'ID' => array(
            'label' => 'Идентификатор',
            'sql'   => 'int(4) unsigned not null auto_increment primary key',
            'type'  => 'Ideal_Hidden'
        ),
        'prev_structure' => array(
            'label' => 'ID родительских структур',
            'sql'   => 'char(15)',
            'type'  => 'Ideal_Hidden'
        ),
        'category_id' => array(
            'label' => 'Категория',
            'sql'   => 'int(11)',
            'type'  => 'CatalogPlus_Category',
            'class' => '\\CatalogPlus\\Structure\\Category\\Getters\\CategoryList'
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
            'type'  => 'CatalogPlus_Price'
        ),
        'annot' => array(
            'label' => 'Описание',
            'sql'   => 'text',
            'type'  => 'Ideal_Area'
        ),
        'template' => array(
            'label'     => 'Тип документа',
            'sql'       => "varchar(20) not null default 'Page'",
            'type'      => 'Ideal_Template',
            'class'     => '\\Ideal\\Structure\\Part\\Getters\\TemplateList',
            'templates' =>  array('Ideal_Page'),
        ),
        'data' => array(
            'label'     => 'Тип товара',
            'sql'       => "varchar(20) not null default 'CatalogPlus_Data'",
            'type'      => 'Ideal_Template',
            'class'     => '\\Ideal\\Structure\\Part\\Getters\\TemplateList',
            'templates' =>  array('CatalogPlus_Data'),
        ),
        'sell' => array(
            'tab'   => 'Данные',
            'label' => 'Скидка',
            'sql'   => "int(11) null default '0'",
            'default'=> 0,
            'type'  => 'Ideal_Integer'
        ),
        'sell_date' => array(
            'tab'   => 'Данные',
            'label' => 'Дата действия скидки',
            'sql'   => 'int(11) null',
            'type'  => 'Ideal_DateAuto'
        ),
        'stock' => array(
            'tab'   => 'Данные',
            'label' => 'Кол-во',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Text'
        ),
        'currency' => array(
            'tab'   => 'Данные',
            'label' => 'Валюта',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Select',
            'values'=> array(
                'руб' => 'руб',
                'usd' => 'usd'
            )
        ),
        'coefficient' => array(
            'tab'   => 'Данные',
            'label' => 'Item',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Text'
        ),
        'img' => array(
            'label' => 'Картинка',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Image'
        ),
        'date_create' => array(
            'tab'   => 'SEO',
            'label' => 'Дата создания',
            'sql'   => 'int(11) not null',
            'type'  => 'Ideal_DateSet'
        ),
        'date_mod' => array(
            'tab'   => 'SEO',
            'label' => 'Дата модификации',
            'sql'   => 'int(11) not null',
            'type'  => 'Ideal_DateAuto'
        ),
        'title' => array(
            'tab'   => 'SEO',
            'label' => 'Title',
            'sql'   => 'text',
            'type'  => 'Ideal_Area'
        ),
        'keywords' => array(
            'tab'   => 'SEO',
            'label' => 'Keywords tag',
            'sql'   => 'text',
            'type'  => 'Ideal_Area'
        ),
        'description' => array(
            'tab'   => 'SEO',
            'label' => 'Description tag',
            'sql'   => 'text',
            'type'  => 'Ideal_Area'
        ),
        'is_active' => array(
            'label' => 'Отображать на сайте',
            'sql'   => 'bool',
            'type'  => 'Ideal_Checkbox'
        ),
    ),
);
