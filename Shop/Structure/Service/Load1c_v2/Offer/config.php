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
        'price' => array(
            'path' => 'Цены/Цена[not(preceding-sibling::`Цена/ЦенаЗаЕдиницу <= `ЦенаЗаЕдиницу)'.
                ' and not(following-sibling::`Цена/ЦенаЗаЕдиницу < `ЦенаЗаЕдиницу)]/ЦенаЗаЕдиницу'
        ),
        'currency' => array(
            'path' => 'Цены/Цена/Единица[1]',
        ),
        'coefficient' => array(
            'path' => 'Цены/Цена/Коэффициент[1]',
        ),
    ),
);
