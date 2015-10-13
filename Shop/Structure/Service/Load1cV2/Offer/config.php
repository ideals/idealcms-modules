<?php
// символ ` заменяется на нэймспэйс
return array(
    'key'    => 'Ид',
    'fields' => array(
        'name'       => 'Наименование',
// todo то, что помечено на удаление — вообще не добавлять к выгрузке
//        'ПометкаУдаления'   => 'is_active',
        'dir_params' => array(
            'path'  => 'ЗначенияСвойств/ЗначенияСвойства',
            'field' => array(
                'dir_id_1c' => 'Ид',
                'dir_value_id' => 'Значение',
            ),
        ),
    ),
    'priceFields'   => array(
        'price'     => 'Цены/Цена[not(preceding-sibling::`Цена/ЦенаЗаЕдиницу <= `ЦенаЗаЕдиницу)'.
            ' and not(following-sibling::`Цена/ЦенаЗаЕдиницу < `ЦенаЗаЕдиницу)]/ЦенаЗаЕдиницу',
        'currency'  => 'Цены/Цена/Валюта[1]',
    ),
    'priceRests'    => array(
        'rest' => 'Остатки/Остаток/Склад/Количество'.
            '[not(preceding-sibling::`Склад/Количество > text() or following-sibling::`Склад/Количество > text())]'
    ),
);
