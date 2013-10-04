<?php

namespace CatalogPlus\Structure\Good\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;

class Model extends ModelAbstract{

    public function getAboutGood(){
        $db = Db::getInstance();
        $good = $this->path;
        $good = end($good);
        $good['properties'] = unserialize($good['properties']);
        $good['properties']['Артикул'] = $good['articul'];
        $good['imgs'] = explode('|:|', $good['imgs']);
        array_unshift($good['imgs'],$good['img']);
        $_sql = "SELECT * FROM i_offers_good WHERE good_id='{$good['id_1c']}' ORDER BY price,size";
        $good['offers'] = $db->queryArray($_sql);
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