<?php

// Новости
return array(
    'params' => array (
        'in_structures' => array('Shop_Order'), // в каких структурах можно создавать эту структуру
        'elements_cms'  => 20,            // количество элементов в списке в CMS
        'elements_site' => 15,            // количество элементов в списке на сайте
        'field_name'    => '',            // поле для входа в список потомков
        'field_sort'    => 'ID DESC', // поле, по которому проводится сортировка в CMS
        'field_list'    => array('order_id', 'good_id_1c', 'offer_id_1c', 'count', 'sum')
     ),
    'fields'   => array (
        'ID' => array(
            'label' => 'Номер заказа',
            'sql'   => 'int(4) unsigned not null auto_increment primary key',
            'type'  => 'Ideal_Hidden'
        ),
        'prev_structure' => array(
            'label' => 'ID родительских структур',
            'sql' => 'char(15)',
            'type' => 'Ideal_Hidden'
        ),
        'order_id' => array(
            'label' => 'ID заказа',
            'sql'   => 'char(11)',
            'type'  => 'Ideal_Hidden'
        ),
        'good_id_1c' => array(
            'label' => 'ID 1c товара',
            'sql'   => 'varchar(45) not null',
            'type'  => 'Ideal_Text'
        ),
        'offer_id_1c' => array(
            'label' => 'ID 1c предложения',
            'sql'   => 'varchar(45) not null',
            'type'  => 'Ideal_Text'
        ),
        'count' => array(
            'label' => 'Количество',
            'sql'   => 'int(5) default 1',
            'type'  => 'Ideal_Text'
        ),
        'sum' => array(
            'label' => 'Сумма',
            'sql'   => 'int(11)',
            'type'  => 'Ideal_Text'
        ),
    ),
);
