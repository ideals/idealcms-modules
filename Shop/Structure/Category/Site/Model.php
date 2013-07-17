<?php
namespace Shop\Structure\Category\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{
    public function getGoods()
    {
        if (!isset($this->object['id_1c'])) {
            //return array();
        }
        $db = Db::getInstance();
        $categoryId = $this->object['ID'];
        $_sql = "SELECT good_id FROM i_good_category WHERE category_id='{$categoryId}'";
        $goodIdsArr = $db->queryArray($_sql);
		if (count($goodIdsArr) == 0) {
			return array();
		}
        $goodIs = array();
        foreach ($goodIdsArr as $good) {
            $goodIs[] = "'" . $good['good_id'] . "'";
        }
        $goodIs = implode(',', $goodIs);

        $_sql = "SELECT * FROM i_shop_structure_good WHERE is_active=1 AND ID IN ({$goodIs}) ORDER BY name";
        $goods = $db->queryArray($_sql);
        return $goods;
    }


    public function detectPageByUrl($url, $path)
    {
        $this->tagParam = $this->setTagParamName($path);
        if ($this->params['is_query_param']) {
            // Категория определяется через QUERY_STRING
            $request = new Request();
            $tag = $request->{$this->tagParam};
            if ($tag == '') {
                // Категория не указана, выходим
                return $url;
            }
            // TODO сделать проверку, что $url на этом этапе должен быть пустой или содержать один элемент?
            $url = explode('/', $tag); // В тэге могут быть подкатегории
        } else {
            $tagName = reset($url);
            if ($this->tagParam != $tagName) {
                // Первый элемент URL не обозначает категорию, значит это статья
                return $url;
            }
            array_shift($url);
        }

        if (count($url) == 1) {
            // Для первого уровня категорий используем небольшой хак — кэширование категорий
            $url = $this->detectPageByTag($url, $path);
        } else {
            // Для вложенных категорий используем стандартное средство обнаружения страницы
            $url = parent::detectPageByUrl($url, $path);
        }

        return $url;
    }


    public function detectPageByTag($url, $path)
    {
        $this->object = false;
        $list = $this->readCategories();
        foreach ($list as $v) {
            if ($v['url'] == $url[0]) {
                $this->object = $v;
                $this->path[] = $v;
                break;
            }
        }
        // TODO \/ надо бы продумать этот момент \/
        return array();
    }


    public function setTagParamName($path)
    {
        $config = Config::getInstance();
        $structure = $config->getStructureByName('Ideal_DataList');
        $dataList = new \Ideal\Structure\DataList\Admin\ModelAbstract($structure['ID']);
        $end = end($path);
        $spravochnik = $dataList->getByParentUrl($end['url']);
        $this->tagParamName = $spravochnik['url'];
        $this->structurePath = $structure['ID'] . '-' . $spravochnik['ID'];
        $this->path = array($structure, $spravochnik);
        return $this->tagParamName;
    }


    public function readCategories()
    {
        if (!isset($this->tagParam)) {
            $this->tagParam = $this->setTagParamName($this->path);
        }
        if (!isset($this->categories)) {
            $db = Db::getInstance();
            $_sql = "SELECT * FROM {$this->_table} WHERE structure_path='{$this->structurePath}' AND is_active=1";
            $this->categories = $db->queryArray($_sql);
        }
        return $this->categories;
    }


    public function getCategories($urlAll)
    {
        $config = Config::getInstance();
        $list = $this->readCategories();
        $first = array(
            'name'  => 'Все статьи',
            'link'  => $urlAll . $config->urlSuffix,
            'class' => ''
        );

        if ($this->object == null) {
            // Не выбрана ни одна категория
            $first['class'] = 'active';
            $tag = '';
        } else {
            $tag = $this->object['url'];
        }

        foreach ($list as $k => $v) {
            $list[$k]['link'] = $this->getUrl($urlAll, $v);
            $list[$k]['class'] = ($v['url'] == $tag) ? 'active' : '';
        }

        if (strpos($_SERVER['REQUEST_URI'], $urlAll) === 0) {
            // Первый элемент добавляем только когда категории запрашиваются со своего URL
            array_unshift($list, $first);
        }

        return $list;
    }


    public function getUrl($prefix, $element)
    {
        if ($this->params['is_query_param']) {
            $config = Config::getInstance();
            $url = $prefix . $config->urlSuffix . '?tag=' . $element['url'];
        } else {
            $urlModel = new \Ideal\Field\Url\Model();
            $url = $urlModel->getUrlWithPrefix($element, $prefix . '/' . $this->tagParam);
        }
        return $url;
    }


    public function getStructureElements()
    {
        return array();
    }


    public function getCurrent()
    {
        if (isset($this->object)) {
            return $this->object;
        } else {
            return false;
        }
    }

}