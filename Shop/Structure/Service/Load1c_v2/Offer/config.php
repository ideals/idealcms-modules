<?php
/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 16.07.2015
 * Time: 17:57
 */
// символ ` заменяется на нэймспэйс
return array(
    'key'    => 'Ид',
    'fields' => array(
        'Наименование'  => 'name',
        'dir_params'    => array(
            'path'  => 'ЗначенияСвойств/ЗначенияСвойства',
            'field' => array(
                'dir_id_1c' => 'Ид',
                'dir_value_id' => 'Значение',
            ),
        ),
    ),
    'priceFields'   => array(
        'price'     => array(
            'path'  =>   'Цены/Цена[not(preceding-sibling::`Цена/ЦенаЗаЕдиницу <= `ЦенаЗаЕдиницу)'.
                ' and not(following-sibling::`Цена/ЦенаЗаЕдиницу < `ЦенаЗаЕдиницу)]/ЦенаЗаЕдиницу',
        ),
        'currency'  => array(
            'path'  => 'Цены/Цена/Валюта[1]',
        ),
    ),
    'priceRests'    => array(
        'rests' => array(
            'path'  => 'Остатки/Остаток/Количество',
        ),
    ),
);
