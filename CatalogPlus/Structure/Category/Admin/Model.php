<?php
namespace CatalogPlus\Structure\Category\Admin;

use Ideal\Core\Db;
use Ideal\Field\Url;

class Model extends \Ideal\Structure\Part\Admin\ModelAbstract
{
    protected $category = array();
    private $glue = '|:|';

    public function getGlue()
    {
        return $this->glue;
    }

    /**
     * @param $name
     * @param array $morePar
     * @return bool
     *
    public function getIdCategory($name, $morePar = array())
    {
        if ($name == '') return false;
        if ($this->category[$name]) return $this->category[$name];

        $arrCategory = explode($this->glue, $name);

        $fullName = NULL;
        while (count($arrCategory)) {
            $fullName .= array_shift($arrCategory);
            if ($this->category[$fullName]) {
                $fullName .= $this->glue;
            } else {
                $this->createCategory($fullName, $morePar);
                if(count($arrCategory)) $fullName .= $this->glue;
            }

        }

        return $this->category[$fullName];

    }*/

    public function getIdCategory($nameCategory)
    {
        if (!isset($this->category[$nameCategory])) return false;
        return $this->category[$nameCategory];
    }

    public function getNameCategory($idCategory)
    {
        if (!isset($this->category[$idCategory])) return false;
        return $this->category[$idCategory];
    }

    /*
    public function loadCategory()
    {
        $db = Db::getInstance();
        $table = $this->_table;
        $_sql = "SELECT * FROM {$table} ORDER BY cid";
        $result = $db->queryArray($_sql);
        $arr = array();
        foreach ($result as $v) {
            $arr = $this->getArrName($arr, $v['lvl'], $v['name']);
            $name = implode($this->glue, $arr);
            $this->category[$name] = $v;
        }
    }*/

    /**
     * Выполняет загрузку категорий на сайте.
     * @param string $key Указывает по какому ключу стоить массив, для 1с это будет id_1c для остальных случаев name
     */
    public function loadCategory($key = '1c_id'){
        $db = Db::getInstance();
        $table = $this->_table;
        $_sql = "SELECT * FROM {$table} ORDER BY cid";
        $result = $db->queryArray($_sql);
        foreach($result as $k => $v){
            $this->category[$v['id_1c']] = $v;
        }
    }

    private function getArrName($arr, $lvl, $addName)
    {
        if ((count($arr) + 1) < $lvl) return false;
        if (!isset($addName)) return false;
        $arr[$lvl] = $addName;
        for ($i = $lvl + 1; $i <= count($arr); $i++) {
            unset($arr[$i]);
        }
        return $arr;
    }

    private function createCategory($name, $morePar = array())
    {
        $pos = strrpos($name, $this->glue);
        if ($pos !== false) {
            $pos += strlen($this->glue);
        }
        $child = substr($name, $pos);
        $parent = substr($name, 0, strrpos($name, $this->glue));

        $cid = new \Ideal\Field\Cid\Model(6, 3);

        $newCid = 1;
        $newLvl = 1;
        $nextParentCid = '';

        if ($parent != '') {
            // Если есть предок, определяем cid и lvl потомка
            $parent = $this->category[$parent];
            $newLvl = $parent['lvl'] + 1;
            $nextParentCid = $cid->setBlock($parent['cid'], $newLvl, 1, true);
            foreach ($this->category as $k => $v) {
                if ($v['cid'] == $nextParentCid) {
                    $newCid += 1;
                    $nextParentCid = $cid->setBlock($nextParentCid, $newLvl, $newCid, true);
                }
            }

        } else {
            $nextParentCid = $cid->setBlock('', $newLvl, 1, true);
            foreach ($this->category as $k => $v) {
                if ($v['cid'] == $nextParentCid) {
                    $newCid += 1;
                    $nextParentCid = $cid->setBlock($nextParentCid, $newLvl, $newCid, true);
                }

            }
        }
        $newCid = $cid->setBlock($nextParentCid, $newLvl, $newCid, true);

        $db = Db::getInstance();
        $table = $this->_table;
        $structurePath = $this->structurePath;

        $insert = array(
            //'id_1c',
            //'structure_path' => $structurePath,
            'cid' => $newCid,
            'lvl' => $newLvl,
            'structure' => 'Shop_Category',
            'template' => 'Ideal_Page',
            'name' => $child,
            'url' => Url\Model::translitUrl($child),
            'num' => 1,
            'date_create' => time(),
            'date_mod' => time()
        );
        foreach ($morePar as $k => $v) {
            $insert[$k] = $v;
        }
        $id = $db->insert($table,$insert);
        $this->category[$name] = $insert;
        $this->category[$name]['ID'] = $id;
    }
}