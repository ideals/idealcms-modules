<?php
return array(
    'key' => 'Ид',
    'fields' => array(
        'orderId1c' => 'Ид',
        'orderNumber1c' => 'Номер1С',
        'is_active' => 'ПометкаУдаления',
        'date_create' => 'Дата1С',
        'sum' => 'Сумма',
        'order_comment' => 'Комментарий',
        'delivery_address' => 'ЗначенияРеквизитов/ЗначениеРеквизита[`Наименование="Адрес доставки"]/Значение',
        'currency' => 'Валюта',
        'goods'      => array(
            'field' => array(
                'good_id' => 'Ид',
                'name' => 'Наименование',
                'count' => 'Количество',
                'good_price' => 'Цена',
                'good_sum' => 'Сумма',
                'good_discount' => 'Скидки/Скидка/Сумма',
            ),
            'path' => 'Товары/Товар'
        ),
        'customer'      => array(
            'field' => array(
                'id_1c' => 'Ид',
                'name' => 'Наименование',
                'phone' => 'Контакты/Контакт[`Тип="Телефон рабочий"]/Значение',
                'email' => 'Контакты/Контакт[`Тип="Электронная почта"]/Значение',
            ),
            'path' => 'Контрагенты/Контрагент'
        ),
    ),
    'updateDbFields'  => array(
        'ID'        => 'ID',
        'pos'       => 'pos'
    ),
);
