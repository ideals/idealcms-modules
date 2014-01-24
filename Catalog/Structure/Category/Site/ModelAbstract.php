<?php
namespace Catalog\Structure\Category\Site;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Request;

class ModelAbstract extends \Ideal\Structure\Part\Site\ModelAbstract
{
    protected $categories;
    protected $current;
    protected $tagParam;

    public function detectPageByUrl($path, $url)
    {
        $this->tagParam = $this->setTagParamName($path);
        if (isset($this->params['is_query_param']) && $this->params['is_query_param']) {
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
                $this->pageData = $v;
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
        $dataList = new \Ideal\Structure\DataList\Admin\ModelAbstract('0-' . $structure['ID']);
        $end = end($path);

        $rpath = $path;
        array_pop($rpath);
        rsort($rpath);
        foreach ($rpath as $rp) {
            if ($rp['structure'] = 'Catalog_Catalog') {
                $spravochnicUrl = $rp['url'];
                break;
            }
        }
        $this->prevStructure = $end['prev_structure'];
        $this->tagParamName = $spravochnicUrl;
        $this->path = array($structure, $spravochnicUrl);
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
        if (isset($this->params['is_query_param']) && $this->params['is_query_param']) {
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

    /**
     * Функция получения контенда для страницы
     */
    protected function getTemplate(){
        $config = Config::getInstance();
        $db = Db::getInstance();
        // Определение prev_structure для поиска в таблице с данными
        $prev_structure = $config->getStructureByName($this->pageData['structure']);
        $prev_structure = $prev_structure['ID'];
        $prev_structure = $prev_structure . '-' . $this->pageData['ID'];
        // Определение таблицы из которой нужно производить загрузку контента
        $table = explode('_', $this->pageData['template']);
        $table = end($table);
        $table = strtolower($table);
        $table = $config->db['prefix'] . 'ideal_template_' . $table;
        // Запрос на получение контента
        $_sql = "SELECT * FROM {$table} WHERE prev_structure='{$prev_structure}' LIMIT 1";
        $result = $db->queryArray($_sql);
        // Запись в данные о страницы самого контента
        $this->pageData['template'] = $result[0];
    }

    public function getCurrent()
    {
        if (isset($this->pageData)) {
            $this->getTemplate();
            return $this->pageData;
        } else {
            return false;
        }
    }


    /**
     * Получение всех категорий
     * @ param int $idCategory ID группы для определения prev_structure
     */
    public function getAllCategory($idCategory)
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'catalog_structure_category';
        $_sql = "SELECT * FROM {$table} WHERE prev_structure=(SELECT prev_structure FROM {$table} WHERE ID={$idCategory} LIMIT 1) AND is_active=1";
        $cat = $db->queryArray($_sql);
        // Построение правильных URL
        $url = new \Ideal\Field\Url\Model();
        $url->setParentUrl($this->path);
        if (is_array($cat) and count($cat) != 0 ) {
            foreach ($cat as $k => $v) {
                $cat[$k]['link'] = $url->getUrl($v);
            }
        }
        return $cat;
    }

}