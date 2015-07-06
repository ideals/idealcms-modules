<?php
namespace Shop\Structure\Service\Load1c_v2;

use Ideal\Field\Cid;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:34
 */

class NewCategory
{
    protected $result = array();
    protected $answer = array();
    protected $tmp = array();

    /**
     * @param DbCategory $dbCategory
     * @param XmlCategory $xmlCategory
     */
    public function parse($dbCategory, $xmlCategory)
    {
        // Забираем реззультаты категорий из БД
        $this->tmp['dbResult'] = $dbCategory->parse();
        // Забираем результаты категорий из xml
        $this->tmp['xmlResult'] = $xmlCategory->parse();
        // сравниваем результаты
        $this->diff();
    }

    public function answer()
    {
        return $this->answer;
    }

    public function getData()
    {
        return $this->result;
    }

    protected function diff()
    {
        // Разница между xml->БД выгрузками - на добавление
        $this->result['add'] = array_diff_key($this->tmp['xmlResult'], $this->tmp['dbResult']['id_1c']);

        // Разница между БД->xml выгрузками - на удаление
        $this->result['delete'] = array_diff_key($this->tmp['dbResult']['id_1c'], $this->tmp['xmlResult']);

        // Пересечение xml и БД выгрузки - на обновление
        $this->result['update'] = array_intersect_key($this->tmp['xmlResult'], $this->tmp['dbResult']['id_1c']);

        $this->buildUpdate();
    }


    /**
     * Меняем cid и
     * @param string $k id_1c
     * @param array $val Массив запроса от бд
     * @param Cid\Model $cidModel
     */
    protected function updateCid($k, $val, $cidModel)
    {
        // если нет в массиве резал апдейт - не надо ничео делать
        if (isset($val['old_cid'])) {
            $cid = $val['old_cid'];
            unset($this->tmp['update'][$k]['old_cid']);
        } else {
            $cid = $val['cid'];
        }
        $parentCid = $cidModel->getParents($cid);
        $id_1c = $this->tmp['dbResult']['cid'][$parentCid]['id_1c'];
        $parent = $this->tmp['xmlResult'][$id_1c];
        if ($parent['cid'] == $this->tmp['dbResult']['cid'][$parentCid]['cid']) {
            $this->tmp['update'][$k]['cid'] = $this->tmp['xmlResult'][$k]['cid'];
        }

    }

    protected function buildUpdate()
    {
        foreach ($this->tmp['update'] as $key => $v) {
            $diff = array_diff($this->tmp['xmlResult'][$key], $this->tmp['dbResult']['id_1c'][$key]);
            if (!empty($diff)) {
                $this->result['update'][$key] = $this->tmp['xmlResult'][$key];
                $this->result['update'][$key]['old_cid'] = $this->tmp['dbResult']['id_1c'][$key]['cid'];
            }
        }
    }
}
