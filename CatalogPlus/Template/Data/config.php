<?php
// Страница
return array(
    'params' => array(
        'name' => 'Характеристики товара',
    ),
    'fields' => array(
        'ID' => array(
            'label' => 'Идентификатор',
            'sql'   => 'int(8) unsigned not null auto_increment primary key',
            'type'  => 'Ideal_Hidden'
        ),
        'prev_structure' => array(
            'label' => 'ID родительских структур',
            'sql'   => 'char(15)',
            'type'  => 'Ideal_Hidden'
        ),
        'article' => array(
            'label' => 'Артикул',
            'sql'   => 'text',
            'type'  => 'Ideal_Text'
        ),
        'material' => array(
            'label' => 'Материал',
            'sql'   => 'text',
            'type'  => 'Ideal_Area'
        ),
        'composition' => array(
            'label' => 'Состав',
            'sql'   => 'text',
            'type'  => 'Ideal_Area'
        ),
        'packing' => array(
            'label' => 'Упаковка',
            'sql'   => 'text',
            'type'  => 'Ideal_Area'
        )
    )
);
