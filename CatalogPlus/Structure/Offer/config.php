<?php

// Варианты товарного предложения для Catalog_Good
return array(
    'params' => array (
        'in_structures'    => array('Catalog_Good'), // в каких структурах можно создавать эту структуру
        'elements_cms'  => 10,            // количество элементов в списке в CMS
        'elements_site' => 40,            // количество элементов в списке на сайте
        'field_name'    => '',            // поле для входа в список потомков
        'field_sort'    => 'price ASC', // поле, по которому проводится сортировка в CMS
        'field_list'    => array('variant_1', 'variant_2', 'price', 'is_active')
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
        'variant_1' => array(
            'label' => 'Ширина стен',
            'sql'   => 'tinyint',
            'type'  => 'Ideal_Select',
            'values' => array(0 => 'По умолчанию', 100 => '100', 150 => '150')
        ),
        'variant_2' => array(
            'label' => 'Тип стен',
            'sql'   => 'tinyint',
            'type'  => 'Ideal_Select',
            'values' => array('По умолчанию', 'Каркасно-щитовой', 'Каркасный')
        ),
        'price' => array(
            'label' => 'Цена за единицу (если договорная, то 0)',
            'sql'   => 'int default null',
            'type'  => 'Ideal_Price'
        ),
        'is_active' => array(
            'label' => 'Отображать на сайте',
            'sql'   => 'bool',
            'type'  => 'Ideal_Checkbox'
        ),
    ),
);
