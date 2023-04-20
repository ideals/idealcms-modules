<?php
// символ ` заменяется на нэймспэйс
return array(
    'key'    => 'Ид',
    'fields' => array(
        'price_old' => 'Цены/Цена[not(preceding-sibling::`Цена/ЦенаЗаЕдиницу <= `ЦенаЗаЕдиницу)'.
            ' and not(following-sibling::`Цена/ЦенаЗаЕдиницу < `ЦенаЗаЕдиницу)]/ЦенаЗаЕдиницу'
    )
);
