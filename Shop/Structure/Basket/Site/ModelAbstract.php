<?php
namespace Shop\Structure\Basket\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;

class ModelAbstract extends \Ideal\Structure\Part\Site\ModelAbstract
{
    /**
     * TODO определять таблицу автоматически
     * Таблица где хранятся товары
     * @var string
     */
    protected $table = 'i_catalogplus_structure_good';

    protected $tableGood;
    protected $tableOffer;

    public function __construct($prevStructure)
    {
        parent::__construct($prevStructure);
        // Указываем таблицы где хранятся данные
        $config = Config::getInstance();
        $prefix = $config->db['prefix'];
        $this->tableGood = $prefix . 'catalogplus_structure_good';
        $this->tableOffer = $prefix . 'catalogplus_structure_offer';
    }

    public function getGoods()
    {
        if (!isset($_COOKIE['basket'])) {
            return false;
        }
        $basket = json_decode($_COOKIE['basket'], true);
        if (count($basket['goods']) === 0) {
            return false;
        }
        $basket['total'] = 0;
        $basket['count'] = 0;
        foreach ($basket['goods'] as $k => $v) {
            $id = explode('_', $k);
            if (count($id) > 1) {
                $tmp = $this->getGoodInfo($id[0], $id[1]);
            } else {
                $tmp = $this->getGoodInfo($id[0]);
            }
            if ($tmp === false) {
                unset($basket['goods'][$k]);
                continue;
            }
            if (isset($tmp['count'])) {
                if (isset($v['count']) && ((int)$v['count'] > (int)$tmp['count'])) {
                    $v['warning'][] = 'Заказано больше чем есть на складе. Уточняйте у менеджера.';
                }
                unset ($tmp['count']);
            }
            $basket['goods'][$k] = array_merge($v, $tmp);
            $basket['goods'][$k]['total_price'] = $v['count'] * $tmp['sale_price'];
            $basket['total'] += $basket['goods'][$k]['total_price'];
            $basket['count'] += 1;
        }
        return $basket;
    }

    private function getGoodInfo($id, $offer = false)
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        if ($offer === false) {
            // Запрос в базу на получение информации о конкретном товаре
            $sql = "SELECT e.*, (CEIL(((100-e.sell)/100)*e.price)) AS sale_price
                    FROM {$this->tableGood} AS e
                    WHERE ID = {$id} AND e.is_active = 1
                    LIMIT 1";
        } else {
            // Запрос в базу на получение информации о конкретном предложении для товара
            $sql = "SELECT o.*, (CEIL(((100-g.sell)/100)*o.price)) AS sale_price
                    FROM {$this->tableOffer} AS o
                    INNER JOIN {$this->tableGood} as g ON (g.ID = {$id})
                    WHERE o.ID = {$offer} AND o.is_active = 1 AND g.ID= {$id}
                    LIMIT 1";
        }
        $allPrice = $db->select($sql);
        if (count($allPrice) === 0) {
            return false;
        }
        $allPrice = $allPrice[0];
        if ($offer !== false) {
            $field = $config->getStructureByName('CatalogPlus_Offer');
            $field = $field['fields'];
            foreach ($field as $k => $v) {
                if ($v['type'] != 'Ideal_Select') {
                    unset($field[$k]);
                }
            }
            $tmp = array_keys($field);
            foreach ($tmp as $v) {
                if ($allPrice[$v] != '0') {
                    $allPrice['offer'] = array(
                        'name' => $field[$v]['label'],
                        'value' => $field[$v]['values'][$allPrice[$v]]
                    );
                }
            }
        }
        // TODO url для товаров
        $allPrice['url'] = $allPrice['url'] . $config->urlSuffix;
        return $allPrice;
    }


    public function getStructureElements()
    {
        return array();
    }

}
