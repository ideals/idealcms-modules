<?php
namespace Articles\Structure\Article\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Core\Util;

class ModelAbstract extends \Ideal\Core\Site\Model
{
    /**
     * @var $categoryModel \Articles\Structure\Category\Site\Model
     */
    protected $categoryModel;
    protected $currentCategory;

    public function detectPageByUrl($path, $url)
    {
        // Определяем, нет ли в URL категории
        $this->categoryModel = new \Articles\Structure\Category\Site\Model($this->structurePath);
        $url = $this->categoryModel->detectPageByUrl($path, $url);
        if (count($url) == 0) {
            // Прошло успешно определение страницы категории, значит статью определять не надо
            $this->path = $path;
            return $this;
        }

        $articleUrl = array_shift($url);

        if (count($url) > 0) {
            // У статьи не может быть URL с несколькими уровнями вложенности
            $this->is404 = true;
            return $this;
        }

        $db = Db::getInstance();

        $_sql = "SELECT * FROM {$this->_table} WHERE url='{$articleUrl}' LIMIT 1";

        $list = $db->select($_sql); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($list[0]['ID'])) {
            $this->path = $path;
            $this->is404 = true;
            return $this;
        }
        $list[0]['structure'] = 'Articles_Article';

        $this->path = array_merge($path, $list);
        $this->pageData = end($list);

        $request = new Request();
        $request->action = 'detail';

        return $this;
    }


    public function detectCurrentCategory()
    {
        if (!isset($this->categoryModel)) {
            // Если категория не была определена на этапе DetectPageByUrl, то нужно
            // проверить, нет ли категории в query_string
            $this->categoryModel = new \Articles\Structure\Category\Site\Model('');
            $this->categoryModel->detectPageByUrl($this->path, array());
        }

        $this->currentCategory = $this->categoryModel->getCurrent();
        if ($this->currentCategory) {
            $this->pageData = $this->currentCategory;
        }
    }


    public function getCategories()
    {
        $parentUrl = $this->getParentUrl();
        return $this->categoryModel->getCategories($parentUrl);
    }

    public function getStructureElements()
    {
        $this->categoryModel = new \Articles\Structure\Category\Site\Model($this->prevStructure);
        $this->categoryModel->setPath($this->path);
        $this->params['elements_site'] = 9999;
        $articles = $this->getList(1);
        $categories = $this->getCategories();
        return array_merge($categories, $articles);
    }

    public function getTemplatesVars()
    {
        if ($this->categoryModel->getCurrent()) {
            return $this->categoryModel->getTemplatesVars();
        } else {
            return parent::getTemplatesVars();
        }
    }

    /**
     * @param int $page Номер отображаемой страницы
     * @return array Полученный список элементов
     */
    public function getList($page = null)
    {
        $list = parent::getList($page);

        // Построение правильных URL
        $url = new \Ideal\Field\Url\Model();
        $url->setParentUrl($this->path);
        if (is_array($list) and count($list) != 0 ) {
            foreach ($list as $k => $v) {
                $list[$k]['link'] = $url->getUrl($v);
            }
        }

        return $list;
    }

    /**
     * Добавление к where-запросу фильтра по category_id
     * @param string $where Исходная WHERE-часть
     * @return string Модифицированная WHERE-часть, с расширенным запросом, если установлена GET-переменная category
     */
    protected function getWhere($where)
    {
        $and = '';
        if ($where != '') {
            $where = 'WHERE ' . $where;
            $and = ' AND ';
        }

        if ($this->currentCategory) {
            // Вывод статей только определённой категории
            $config = Config::getInstance();
            $table = $config->db['prefix'] . 'articles_medium_categorylist';
            $where .= $and . "e.ID IN (SELECT article_id FROM {$table}
                                              WHERE category_id={$this->currentCategory['ID']})";
        }
        $time = time();
        $where .= " AND is_active=1 AND date_create < {$time}";

        return $where;
    }

    public function detectPath()
    {
        $config = Config::getInstance();
        $db = Db::getInstance();

        $article = $this->pageData;
        list($parentStructure, $parentId) = explode('-', $article['prev_structure']);
        $structure = $config->getStructureById($parentStructure);

        // Находим предка — структуру статей
        $parentClassName = Util::getClassName($structure['structure'], 'Structure') . '\\Site\\Model';

        /** @var \Ideal\Structure\Part\Site\Model $parentModel */
        $parentModel = new $parentClassName($article['prev_structure']);
        $parentModel->setPageDataById($parentId);

        $path = $parentModel->detectPath();

        $this->path = $path;
        $this->path[] = $article;

        return $this->path;
    }

    public function getHeader()
    {
        $header = '';
        if (isset($this->pageData['content'])) {
            // Если есть шаблон с контентом, пытаемся из него извлечь заголовок H1
            list($header, $text) = $this->extractHeader($this->pageData['content']);
            $this->pageData['content'] = $text;
        } elseif (isset($this->pageData['template']['content'])) {
            // Извлекаем заголовок из текста для списка статей
            list($header, $text) = $this->extractHeader($this->pageData['template']['content']);
            $this->pageData['template']['content'] = $text;
        }

        if ($header == '') {
            // Если заголовка H1 в тексте нет, берём его из названия name
            $header = $this->pageData['name'];
        }
        return $header;
    }
}
