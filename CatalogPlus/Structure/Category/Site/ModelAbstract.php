<?php

namespace CatalogPlus\Structure\Category\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Field;
use Ideal\Core\Util;

class ModelAbstract extends \Ideal\Structure\Part\Site\ModelAbstract
{
    protected $categories;
    protected $current;
    protected $tagParam;

    /** @var  \CatalogPlus\Structure\Good\Site\Model */
    protected $goods;


    public function detectPageByUrl($path, $url)
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
            $url = parent::detectPageByUrl($path, $url);
        }

        return $url;
    }


    public function detectPageByTag($url, $path)
    {
        $this->pageData = false;
        $list = $this->readCategories();
        foreach ($list as $v) {
            if ($v['url'] == $url[0]) {
                $v['structure'] = 'CatalogPlus_Category';
                $this->pageData = $v;
                $this->path[] = $v;
                break;
            }
        }
        if ($this->pageData === false) return array('нет','такой','категории');
        return array();
    }


    public function setTagParamName($path)
    {
        $config = Config::getInstance();
        $structure = $config->getStructureByName('Ideal_DataList');
        $dataList = new \Ideal\Structure\DataList\Admin\ModelAbstract('0-' . $structure['ID']);
        $end = end($path);
        $spravochnik = $dataList->getByParentUrl($end['url']);
        $this->tagParamName = $spravochnik['url'];
        $this->prevStructure = $structure['ID'] . '-' . $spravochnik['ID'];
        //$this->path = array($structure, $spravochnik);
        return $this->tagParamName;
    }


    public function readCategories()
    {
        if (!isset($this->tagParam)) {
            $this->tagParam = $this->setTagParamName($this->path);
        }
        if (!isset($this->categories)) {
            $db = Db::getInstance();
            $_sql = "SELECT * FROM {$this->_table} WHERE prev_structure='{$this->prevStructure}' AND is_active=1";
            $this->categories = $db->queryArray($_sql);
        }
        return $this->categories;
    }


    public function getCategories($urlAll)
    {
        $config = Config::getInstance();
        $list = $this->readCategories();
        $first = array(
            'name' => 'Все',
            'link' => 'href="' . $urlAll . $config->urlSuffix . '"',
            'class' => ''
        );

        if ($this->pageData == null) {
            // Не выбрана ни одна категория
            $first['class'] = 'active';
            $tag = '';
        } else {
            $tag = $this->pageData['url'];
        }

        foreach ($list as $k => $v) {
            $list[$k]['link'] = 'href="' . $this->getUrl($urlAll, $v) . '"';
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


    public function setGoods($model)
    {
        $this->goods = $model;
    }

    public function getGoods()
    {
        return $this->goods;
    }

    /**
     * Установка свойств объекта по данным из объекта $model
     *
     * Вызывается при копировании данных из одной модели в другую
     * @param array $model Массив переменных объекта
     * @return $this Либо ссылка на самого себя, либо новый объект модели
     */
    public function setVars($model)
    {
        $this->setGoods($model);
        $model = parent::setVars($model);
        return $model;
    }

    /**
     * Определение главная ли эта страница товара или внутренняя
     * @return bool Возвращает true если это не главная страница товара
     */
    public function isNotIndex()
    {
        $end = end($this->getPath());
        if ($end['structure'] != $this->pageData['structure']) {
            return false;
        }
        return true;
    }
}
