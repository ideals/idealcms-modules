<?php
namespace Articles\Structure\Category\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Field;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{

    public function getCategories($urlAll)
    {
        $db = Db::getInstance();
        // structure_path='{$this->structurePath}' AND
        $_sql = "SELECT * FROM {$this->_table} WHERE is_active=1";
        $list = $db->queryArray($_sql);

        $config = Config::getInstance();
        $first = array(
            'name'  => 'Все статьи',
            'link'  => $urlAll . $config->urlSuffix,
            'class' => ''
        );

        $request = new Request();
        $tag = $request->tag;

        if ($tag == '') {
            $first['class'] = 'active';
        }

        foreach ($list as $k => $v) {
            $list[$k]['link'] = $urlAll . $config->urlSuffix . '?tag=' . $v['url'];
            $list[$k]['class'] = ($v['url'] == $tag) ? 'active' : '';
        }

        array_unshift($list, $first);

        return $list;
    }


    public function getStructureElements()
    {
        return array();
    }

}
