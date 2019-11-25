<?php
namespace CatalogPlus\Structure\Brand\Admin;

use Ideal\Core\Db;
use Ideal\Field\Url;
use Ideal\Core\Config;
use Ideal\Structure\Roster;

class Model extends Roster\Admin\ModelAbstract
{
    private $type = array();

    public function loadType()
    {
        $prevStructure = $this->prevStructure;
        $db = Db::getInstance();
        $type = array();

        $_sql = "SELECT ID, name FROM i_catalogplus_structure_brand WHERE prev_structure = '{$prevStructure}'";
        $types = $db->select($_sql);
        foreach ($types as $elem) {
            $key = trim(strtolower($elem['name']), " \t");
            $type[$key] = $elem['ID'];
        }
        $this->type = $type;

    }

    public function getIdType($name)
    {
        $name = trim(strtolower($name), " \t");
        if (isset($this->type[$name])) {
            return $this->type[$name];
        } else {
            $db = Db::getInstance();
            $insert = array(
                'prev_structure' => $this->prevStructure,
                'name' => $name,
                'url' => Url\Model::translitUrl($name),
                'date_create' => time(),
                'is_active' => 1
            );
            $id = $db->insert('i_catalogplus_structure_brand', $insert);
            $this->type[$name] = $id;
            //$this->loadType();
            return $id;
        }
    }
}
