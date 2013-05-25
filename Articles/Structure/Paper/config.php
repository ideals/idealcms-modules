<?php

// Новости
return array(
    'params' => array (
        'in_structures'    => array(), // в каких структурах можно создавать эту структуру
        'elements_cms'  => 20,            // количество элементов в списке в CMS
        'elements_site' => 15,            // количество элементов в списке на сайте
        'field_cap'     => '',            // поле для входа в список потомков
        'field_sort'    => 'date_create ASC', // поле, по которому проводится сортировка в CMS
        'field_list'    => array('name', 'is_active', 'date_create')
     ),
    'fields'   => array (
        'ID' => array(
            'label' => 'Идентификатор',
            'sql'   => 'int(4) unsigned not null auto_increment primary key',
            'type'  => 'Ideal_Hidden'
        ),
        'structure_path' => array(
            'label' => 'ID родительских структур',
            'sql'   => 'char(15)',
            'type'  => 'Ideal_Hidden'
        ),
        'category' => array(
            'label' => 'Категория',
            'sql'   => '',
            'type'  => 'Articles_Category',
            'class' => '\\Articles\\Structure\\Category\\Getters\\CategoryList'
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
        'annot' => array(
            'label' => 'Описание',
            'sql'   => 'text',
            'type'  => 'Ideal_Area'
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
