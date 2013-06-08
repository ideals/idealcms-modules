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
        $_sql = "SELECT * FROM {$this->_table} WHERE structure_path='{$this->structurePath}' AND is_active=1";
        $list = $db->queryArray($_sql);
        $url = new \Ideal\Field\Url\Model();

        $config = Config::getInstance();
        $first = array(
            'cap'   => 'Все статьи',
            'url'   => $urlAll . $config->urlSuffix,
            'class' => ''
        );

        $request = new Request();
        $tag = $request->tag;

        if ($tag == '') {
            $first['class'] = 'active';
        }

        $url->setParentUrl($path);
        foreach ($list as $k => $v) {
            $list[$k]['url'] = $urlAll . $config->urlSuffix . '?tag=' . $v['url'];
            $list[$k]['class'] = ($v['url'] == $tag) ? 'active' : '';
        }

        array_unshift($list, $first);

        return $list;
    }

}
