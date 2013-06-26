<?php

// Новости
return array(
    'params' => array (
        'in_structures' => array('Ideal_Part'), // в каких структурах можно создавать эту структуру
        'elements_cms'  => 24,            // количество элементов в списке в CMS
        'elements_site' => 15,            // количество элементов в списке на сайте
        'field_name'    => '',            // поле для входа в список потомков
        'field_sort'    => 'pos DESC',    // поле, по которому проводится сортировка в CMS
        'field_list'    => array('pos', 'name', 'is_active', 'date_create')
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
        'pos' => array(
            'label' => '№',
            'sql'   => 'int not null',
            'type'  => 'Ideal_Text'
        ),
        'name' => array(
            'label' => 'Название',
            'sql'   => 'varchar(255) not null',
            'type'  => 'Ideal_Text'
        ),
        'img' => array(
            'label' => 'Фото большое',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Text'
        ),
        'img_s' => array(
            'label' => 'Фото маленькое',
            'sql'   => 'varchar(255)',
            'type'  => 'Ideal_Text'
        ),
        'info'=> array(
            'label' => 'Аннотация',
            'sql'   => 'text',
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
