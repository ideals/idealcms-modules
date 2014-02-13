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
        'measure' => array(
            'tab'   => 'info',
            'label' => 'Базовая Единица',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_Text'
        ),
        'id_1c' => array(
            'tab'   => 'info',
            'label' => 'id 1c',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_Text'
        ),
        'properties' => array(
            'tab'   => 'info',
            'label' => 'Параметры',
            'sql'   => 'text',
            'type'  => 'Ideal_Text'
        ),
        'full_name' => array(
            'tab'   => 'info',
            'label' => 'Полное имя',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_Text'
        ),
        'sell' => array(
            'tab'   => 'info',
            'label' => 'Скидка',
            'sql'   => 'int(11) null',
            'type'  => 'Ideal_Text'
        ),
        'sell_date' => array(
            'tab'   => 'info',
            'label' => 'Дата действия скидки',
            'sql'   => 'int(11) null',
            'type'  => 'Ideal_Text'
        ),
        'stock' => array(
            'tab'   => 'info',
            'label' => 'Кол-во',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Text'
        ),
        'currency' => array(
            'tab'   => 'info',
            'label' => 'Валюта',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Text'
        ),
        'item' => array(
            'tab'   => 'info',
            'label' => 'Item',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Text'
        ),
        'coefficient' => array(
            'tab'   => 'info',
            'label' => 'Item',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Text'
        ),
        'img' => array(
            'label' => 'Картинка',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Image'
        ),
        'imgs' => array(
            'label' => 'Прочие картинки',
            'sql'   => 'text',
            'type'  => 'Ideal_Text'
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
        'content' => array(
            'label' => 'Текст',
            'sql'   => 'mediumtext',
            'type'  => 'Ideal_RichEdit'
        ),
        'is_active' => array(
            'label' => 'Отображать на сайте',
            'sql'   => 'bool',
            'type'  => 'Ideal_Checkbox'
        ),
    ),
);
