<?php

// Страницы сайта
return array(
    'params' => array(
        'in_structures' => array('Ideal_Part'),
        'structures'    => array('Shop_Category'), // типы, которые можно создавать в этом разделе
        'elements_cms'  => 10, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_cap'     => 'cap', // поля для вывода информации по объекту
        'field_sort'    => 'cid', // поле, по которому проводится сортировка в CMS
        'field_list'    => array('cid!40', 'ID', 'cap', 'date_mod', 'url'),
        'levels'        => 6, // количество уровней вложенности
        'digits'        => 3 // //кол-во разрядов
    ),
    'fields' => array(
        // label - название поля в админке
        // sql   - описание поля для создания его в базе данных
        // type  - тип поля для вывода и обработки в админке
        // 3 - способ вывода данных в админке (в данный момент не работает)
        //      0 - не выводить,
        //      1 - выводить обычным образом,
        //      2 - выводить, но не показывать пользователю,
        //      3 - выводить в закрытом блоке
        // 4 - название функции для получения данных для вывода в админке
        // 5 - значение по-умолчанию
        'ID' => array(
            'label' => 'Идентификатор',
            'sql'   => 'int(8) unsigned not null auto_increment primary key',
            'type'  => 'Ideal_Hidden'
        ),
        'id_1c' => array(
            'label' => 'ID в 1С',
            'sql'   => 'char(37)',
            'type'  => 'Ideal_Text'
        ),
        'structure_path' => array(
            'label' => 'ID родительских структур',
            'sql'   => 'char(15)',
            'type'  => 'Ideal_Hidden'
        ),
        'cid' => array(
            'label' => '№',
            'sql'   => 'char(' . (6 * 3) . ') not null',
            'type'  => 'Ideal_Cid'
        ),
        'lvl' => array(
            'label' => 'Уровень вложенности объекта',
            'sql'   => 'int(1) unsigned not null',
            'type'  => 'Ideal_Hidden'
        ),
        'structure' => array(
            'label' => 'Тип раздела',
            'sql'   => 'varchar(20) not null',
            'type'  => 'Ideal_Select',
            'class' => '\\Ideal\\Structure\\Part\\Getters\\StructureList'
        ),
        'template' => array(
            'label'     => 'Тип документа',
            'sql'       => "varchar(20) not null default 'Page'",
            'type'      => 'Ideal_Template',
            'class'     => '\\Ideal\\Structure\\Part\\Getters\\TemplateList',
            'templates' =>  array('Ideal_Page', 'Ideal_PhpFile'),
        ),
        'cap' => array(
            'label' => 'Название',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_Text'
        ),
        'url' => array(
            'label' => 'URL',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_UrlAuto'
        ),
        'annot' => array(
            'label' => 'Описание',
            'sql'   => 'text',
            'type'  => 'Ideal_Area'
        ),
        'num' => array(
            'label' => 'Количество товаров в категории',
            'sql'   => 'int(11) NOT NULL DEFAULT 0',
            'type'  => 'Ideal_Text'
        ),
        'img' => array(
            'label' => 'Картинка',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Image'
        ),
        'date_create' => array(
            'label' => 'Дата создания',
            'sql'   => 'int(11) not null',
            'type'  => 'Ideal_DateSet'
        ),
        'date_mod' => array(
            'label' => 'Дата модификации',
            'sql'   => 'int(11) not null',
            'type'  => 'Ideal_DateAuto'
        ),
        'is_active' => array(
            'label' => 'Отображать на сайте',
            'sql'   => "bool DEFAULT '0' NOT NULL",
            'type'  => 'Ideal_Checkbox'
        ),
        'is_not_menu' => array(
            'label' => 'Не выводить в меню',
            'sql'   => "bool DEFAULT '0' NOT NULL",
            'type'  => 'Ideal_Checkbox'
        ),
        'is_self_menu'=> array(
            'label' => 'Не выводить своё подменю',
            'sql'   => "bool DEFAULT '0' NOT NULL",
            'type'  => 'Ideal_Checkbox'
        ),
        'is_skip' => array(
            'label' => 'Пропускать уровень',
            'sql'   => "bool DEFAULT '0' NOT NULL",
            'type'  => 'Ideal_Checkbox'
        ),
        'url_full' => array(
            'tab'   => 'SEO',
            'label' => 'URL FULL',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Text'
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
    )
);