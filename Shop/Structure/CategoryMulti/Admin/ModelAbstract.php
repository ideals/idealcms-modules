<?php
namespace Shop\Structure\CategoryMulti\Admin;

use Ideal\Core\Config;

class ModelAbstract extends \Ideal\Structure\Part\Admin\ModelAbstract
{
    public function detectPrevStructure($path)
    {
        $config = Config::getInstance();
        $structure = $config->getStructureByName('Ideal_DataList');
        $dataList = new \Ideal\Structure\DataList\Admin\ModelAbstract('0-' . $structure['ID']);
        $end = end($path);
        $spravochnik = $dataList->getByParentUrl($end['url']);
        $this->tagParamName = $spravochnik['url'];
        $this->prevStructure = $structure['ID'] . '-' . $spravochnik['ID'];
        $this->path = array($structure, $spravochnik);
        return $this->prevStructure;
    }
}
