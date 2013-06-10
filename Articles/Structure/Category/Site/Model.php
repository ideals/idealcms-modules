<?php
namespace Articles\Structure\Category\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Field;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{
    protected $categories;
    protected $current;

    public function readCategories()
    {
        if (!isset($this->categories)) {
            $db = Db::getInstance();
            $_sql = "SELECT * FROM {$this->_table} WHERE structure_path='{$this->structurePath}' AND is_active=1";
            $this->categories = $db->queryArray($_sql);
        }
        return $this->categories;
    }

    public function getCategories($urlAll)
    {
        $list = $this->readCategories();
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


    public function setPath($path)
    {
        $config = Config::getInstance();
        $structure = $config->getStructureByName('Ideal_DataList');
        $dataList = new \Ideal\Structure\DataList\Admin\ModelAbstract($structure['ID']);
        $end = end($path);
        $spravochnik = $dataList->getByParentUrl($end['url']);
        $this->path = array($structure, $spravochnik);
        $this->object = $spravochnik;
        $this->structurePath = $structure['ID'] . '-' . $spravochnik['ID'];
    }


    public function getStructureElements()
    {
        return array();
    }


    public function getCurrent()
    {
        if (isset($this->current)) {
            return $this->current;
        }

        $request = new Request();
        $current = mysql_real_escape_string($request->tag);

        $this->current = false;
        $list = $this->readCategories();
        foreach ($list as $v) {
            if ($v['url'] == $current) {
                $this->current = $v;
                break;
            }
        }

        if ($this->current) {
            $this->path[] = $this->current;
            $this->object = $this->current;
        }

        return $this->current;
    }

}
