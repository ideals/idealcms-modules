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
            // TODO запрос в базу на получение информации о конкретном товаре
            $sql = "SELECT e.*
                    FROM i_catalogplus_structure_good AS e
                    WHERE ID = {$id} AND e.is_active = 1
                    LIMIT 1";
        } else {
            // TODO запрос в базу на получение информации о конкретном предложении для товара
            $sql = "SELECT o.*, g.name, g.url, g.img
                    FROM i_catalogplus_structure_offer AS o
                    INNER JOIN i_catalogplus_structure_good AS g
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
        $allPrice['sale_price'] = ceil((100 - $allPrice['sale']) / 100 * $allPrice['price']);
        return $allPrice;
    }


    public function getStructureElements()
    {
        return array();
    }

}
