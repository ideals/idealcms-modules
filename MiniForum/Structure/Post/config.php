<?php

// Форум
return array(
    'params' => array (
        'in_structures' => array('Ideal_Part', 'MiniForum_Post'), // в каких структурах можно создавать эту структуру
        'elements_cms'      => 10,                      // количество элементов в списке в CMS
        'elements_site'     => 10,                      // количество элементов в списке на сайте
        'field_sort'        => 'date_create',           // поле, по которому проводится сортировка в CMS
        'field_name'        => 'content',               // поля для вывода информации по объекту
        'field_list'        => array('author', 'email', 'date_create', 'is_active')
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
        'parent_id' => array(
            'label' => 'Родительское сообщение',
            'sql'   => 'int(11) default 0',
            'type'  => 'Ideal_Text'
        ),
        'author' => array(
            'label' => 'Автор',
            'sql'   => 'varchar(100)',
            'type'  => 'Ideal_Text'
        ),
        'email' => array(
            'label' => 'E-mail',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Text'
        ),
        'content'=> array(
            'label' => 'Сообщение',
            'sql'   => 'text not null',
            'type'  => 'Ideal_Area'
        ),
        'date_create' => array(
            'label' => 'Дата создания',
            'sql'   => 'int(11) not null',
            'type'  => 'Ideal_DateSet'
        ),
        'is_active' => array(
            'label' => 'Отображать на сайте',
            'sql'   => 'bool',
            'type'  => 'Ideal_Checkbox'
        ),
    ),
);
