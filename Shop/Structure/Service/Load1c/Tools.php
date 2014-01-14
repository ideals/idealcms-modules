<?php
namespace Shop\Structure\Service\Load1c;

class Tools extends ToolsAbstract
{
    public function __construct(){
        $this->tableLink = 'catalogplus_good';
        $this->tableGood = 'catalogplus_structure_good';
        $this->tableCat = 'catalogplus_structure_category';

        $this->fields = array(
            'Ид' => 'id_1c',
            'Наименование' => 'name',
            'БазоваяЕдиница' => 'measure',
            'Картинка' => 'img',
            'ЗначенияСвойств' => array(
                'Артикул' => 'article',
                'Кол-во в упаковке, шт' => 'quantity',
                'Категория на сайте' => 'category',
                'Мощность, Вт' => 'power_w',
                'Размер' => 'size',
                'Средний срок службы, ч' => 'average_life',
                'Тип' => 'type',
                'ДлЦветовая температура, Кина' => 'temperature',
                'Цоколь' => 'socle',
                'Цвет' => 'color',
            ),
            'ЗначенияРеквизитов' => array(
                'Полное наименование' => 'full_name',
                'Вес' => 'weight'
            ),
            /*
            'ХарактеристикиТовара' => array(
                'Размер' => 'size'
            ),
            */
            'Количество' => 'stock',
            'ЦенаЗаЕдиницу' => 'price',
            'Валюта' => 'currency',
            'Единица' => 'item',
            'Коэффициент' => 'coefficient'
        );

        parent::__construct();
    }
}
