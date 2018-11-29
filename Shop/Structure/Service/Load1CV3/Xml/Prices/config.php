<?php
// символ ` заменяется на нэймспэйс
return array(
    'key'    => 'Ид',
    'fields' => array(
        'price'     => 'Цены/Цена[not(preceding-sibling::`Цена/ЦенаЗаЕдиницу <= `ЦенаЗаЕдиницу)'.
            ' and not(following-sibling::`Цена/ЦенаЗаЕдиницу < `ЦенаЗаЕдиницу)]/ЦенаЗаЕдиницу',
        'currency'  => 'Цены/Цена/Валюта[1]',
    )
);
