<?php

// Таблица пользователей
return array(
    'params' => array(
        'in_structures' => array('Ideal_Part'), // в каких структурах можно создавать эту структуру
        'elements_cms' => 10, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_name' => 'name', // поля для вывода информации по объекту
        'field_sort' => 'pos', // поле, по которому проводится сортировка в CMS
        'field_list' => array('ID', 'name', 'pos', 'url'),
        'levels' => 6, // количество уровней вложенности
        'digits' => 3 // //кол-во разрядов
    ),
    'fields'   => array (
        'ID' => array(
            'label' => 'Идентификатор',
            'sql' => 'int(4) unsigned not null auto_increment primary key',
            'type' => 'Ideal_Hidden'
        ),
        'prev_structure' => array(
            'label' => 'ID родительских структур',
            'sql' => 'char(15)',
            'type' => 'Ideal_Hidden'
        ),
        'name' => array(
            'label' => 'Заголовок',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_Text'
        ),
        'url' => array(
            'label' => 'URL',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_UrlAuto'
        ),
        'pos' => array(
            'label' => 'Позиция отображения',
            'sql' => 'int(11) default 1',
            'type' => 'Ideal_Integer'
        ),
        'template' => array(
            'label' => 'Шаблон отображения',
            'sql' => "varchar(255) default 'index.twig'",
            'type' => 'Ideal_Select',
            'medium' => '\\Shop\\Medium\\BasketTabsList\\Model'
        ),
        'annot' => array(
            'label' => 'Аннотация',
            'sql' => 'text',
            'type' => 'Ideal_Area'
        ),
        'date_create' => array(
            'label' => 'Дата создания',
            'sql' => 'int(11) not null',
            'type' => 'Ideal_DateSet'
        ),
        'content' => array(
            'label' => 'Сообщение',
            'sql' => 'text',
            'type' => 'Ideal_RichEdit'
        ),
        'is_active' => array(
            'label' => 'Отображать на сайте',
            'sql' => 'bool',
            'type' => 'Ideal_Checkbox'
        ),
    ),
);