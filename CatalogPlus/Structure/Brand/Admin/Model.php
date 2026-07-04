<?php

namespace CatalogPlus\Structure\Brand\Admin;

use Ideal\Structure\Roster\Admin\ModelAbstract;
use Ideal\Core\Db;
use Ideal\Field\Url;

class Model extends ModelAbstract
{
    private $type = [];

    public function loadType(): void
    {
        $prevStructure = $this->prevStructure;
        $db = Db::getInstance();
        $type = [];

        $_sql = sprintf("SELECT ID, name FROM i_catalogplus_structure_brand WHERE prev_structure = '%s'", $prevStructure);
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
        }

        $db = Db::getInstance();
        $insert = [
            'prev_structure' => $this->prevStructure,
            'name' => $name,
            'url' => Url\Model::translitUrl($name),
            'date_create' => time(),
            'is_active' => 1,
        ];
        $id = $db->insert('i_catalogplus_structure_brand', $insert);
        $this->type[$name] = $id;
        //$this->loadType();
        return $id;

    }
}
