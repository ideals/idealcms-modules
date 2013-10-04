<?php
namespace CatalogPlus\Structure\Category\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{
    public function getAllGoods()
    {
        $goods = array();
        $db = Db::getInstance();
        $config = Config::getInstance();
        $path = $this->getPath();
        $object = end($path);
        $smallCid = rtrim($object['cid'], '0');
        $tableGood = $config->db['prefix'] . 'catalogplus_structure_good';
        $table = $this->_table;
        $_sql = "SELECT * FROM {$tableGood} WHERE category_id IN(SELECT ID FROM {$table} WHERE cid LIKE '{$smallCid}%')";
        $goods = $db->queryArray($_sql);
        foreach ($goods as $k => $v) {
            $goods[$k]['properties'] = unserialize($v['properties']);
            if ($goods[$k]['properties']['Новинка'] == 'да') {
                $goods[$k]['new'] = 1;
            }
            unset($goods[$k]['properties']['Новинка']);
            $goods[$k]['link'] = 'href="/tovar/' . $v['url'] .$config->urlSuffix. '"';
        }
        return $goods;
    }

    public function getMenu($prefix = '/shop')
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $this->_table;
        $smallCid = rtrim($this->object['cid'], '0');
        //$structure_path = $this->object['structure_path'];
        $_sql = "SELECT * FROM {$table} WHERE (cid LIKE '{$smallCid}%' OR lvl=1) AND is_active=1  ORDER BY cid";
        $groups = $db->queryArray($_sql);
        foreach ($groups as $k => $v) {
            if ($v['lvl'] == 1) $groups[$k]['link'] = "href=\"{$prefix}/{$v['url']}{$config->urlSuffix}";
        }

    }

}