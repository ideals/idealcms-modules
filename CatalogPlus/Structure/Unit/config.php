<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

// Журнал действий
return [
    'params' => [
        'in_structures' => ['Ideal_DataList'], // в каких структурах можно создавать эту структуру
        'elements_cms' => 10, // количество элементов в списке в CMS
        'field_name' => '', // поле для входа в список потомков
        'field_sort' => 'name ASC', // поле, по которому проводится сортировка в CMS
        'field_list' => ['ID', 'name', 'id_1c', 'date_create', 'is_active']
    ],
    'fields' => [
        'ID' => [
            'label' => 'ID',
            'sql' => 'int(4) unsigned not null auto_increment primary key',
            'type' => 'Ideal_Hidden',
        ],
        'id_1c' => [
            'label' => 'Идентификатор в 1С',
            'sql' => 'varchar(100) not null',
            'type' => 'Ideal_Text',
        ],
        'prev_structure' => [
            'label' => 'ID родительских структур',
            'sql' => 'char(15)',
            'type' => 'Ideal_Hidden',
        ],
        'date_create' => [
            'label' => 'Дата создания записи',
            'sql' => 'int(11) not null',
            'type' => 'Ideal_DateSet',
        ],
        'date_mod' => [
            'label' => 'Дата модификации',
            'sql'   => 'int(11) not null',
            'type'  => 'Ideal_DateAuto'
        ],
        'name' => [
            'label' => 'Наименование',
            'sql' => 'varchar(100) not null',
            'type' => 'Ideal_Text',
        ],
        'full_name' => [
            'label' => 'Полное наименование',
            'sql' => 'varchar(255) not null',
            'type' => 'Ideal_Text',
        ],
        'is_active' => [
            'label' => 'Активность',
            'sql' => 'bool',
            'type' => 'Ideal_Checkbox',
        ],
    ],
];
