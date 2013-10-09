<?php

namespace CatalogPlus\Structure\Good\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Field\Url;

class Model extends ModelAbstract{

    public function getAboutGood(){
        $db = Db::getInstance();
        $config = Config::getInstance();
        $good = $this->path;
        $good = end($good);
        $good['properties'] = unserialize($good['properties']);
        $good['properties']['Бренд'] = '<a href="/catalog/brand/' . Url\Model::translitUrl($good['properties']['Бренд']) . $config->urlSuffix . '">'
            . $good['properties']['Бренд'] . '</a>';
        $good['properties']['Артикул'] = $good['articul'];
        $good['imgs'] = explode('|:|', $good['imgs']);
        array_unshift($good['imgs'],$good['img']);
        $_sql = "SELECT * FROM i_offers_good WHERE good_id='{$good['id_1c']}' ORDER BY price,size";
        $good['offers'] = $db->queryArray($_sql);
        if (isset($good['sell']) && $good['sell'] != null) {
            $good['oldPrice'] = $good['price'];
            $good['price'] = ceil($good['price'] - $good['price'] / 100 * $good['sell']);
        }
        return $good;
    }
    public function detectPath1()
    {
        $config = Config::getInstance();

        $prevStructure = explode('-', $this->object['prev_structure']);
        $sP = array_shift($prevStructure);
        $i = 0;
        while($sP == '0'){
            $sP = array_shift($prevStructure);
            if(++$i > 10) break;
        }
        $structure = $config->getStructureById($sP);
        $path = array($structure);
        foreach ($prevStructure as $v) {
            $className = \Ideal\Core\Util::getClassName($structure['structure'], 'Structure') . '\\Site\\Model';
            /* @var $structure \Ideal\Core\Model */
            $structure = new $className($sP);
            $structure->setObjectById($v);
            $elements = $structure->getLocalPath();
            $path = array_merge($path, $elements);
            $structure = end($path);
            $sP .= '-' . $structure['ID'];
        }
        $path = array_merge($path, $this->getLocalPath());
        return $path;
    }
}