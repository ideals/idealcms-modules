<?php
namespace Articles\Structure\Article\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Core\Util;

class ModelAbstract extends \Ideal\Core\Site\Model
{
    public function detectPageByUrl($path, $url)
    {
        $articleUrl = array_shift($url);

        if (count($url) > 0) {
            // У статьи не может быть URL с несколькими уровнями вложенности
            $this->path = $path;
            $this->is404 = true;
            return $this;
        }

        $db = Db::getInstance();

        $par = array('url' => $articleUrl);
        $fields = array('table' => $this->_table);
        $_sql = "SELECT * FROM &table WHERE BINARY url = :url LIMIT 1";

        $list = $db->select($_sql, $par, $fields); // запрос на получение всех страниц, соответствующих частям url

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

    public function getStructureElements()
    {
        $this->params['elements_site'] = 9999;
        $articles = $this->getList(1);
        return $articles;
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
     *
     * @param string $where Исходная WHERE-часть
     * @return string Модифицированная WHERE-часть, с расширенным запросом, если установлена GET-переменная category
     */
    protected function getWhere($where)
    {
        if ($where != '') {
            $where = 'WHERE ' . $where;
        }
        $time = time();
        $where .= " AND is_active=1 AND date_create < {$time}";

        return $where;
    }

    public function detectPath()
    {
        $config = Config::getInstance();

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
        // Если есть шаблон с контентом, пытаемся из него извлечь заголовок H1
        if (isset($this->pageData['content']) && !empty($this->pageData['content'])) {
            list($header, $text) = $this->extractHeader($this->pageData['content']);
            $this->pageData['content'] = $text;
        } elseif (!empty($this->pageData['addon'])) {
            // Последовательно пытаемся получить заголовок из всех аддонов до первого найденного
            $addons = json_decode($this->pageData['addon']);
            for ($i = 0; $i < count($addons); $i++) {
                if (isset($this->pageData['addons'][$i]['content'])
                    && $this->pageData['addons'][$i]['content'] !== ''
                ) {
                    list($header, $text) = $this->extractHeader($this->pageData['addons'][$i]['content']);
                    if (!empty($header)) {
                        $this->pageData['addons'][$i]['content'] = $text;
                        break;
                    }
                }
            }
        }

        if ($header == '') {
            // Если заголовка H1 в тексте нет, берём его из названия name
            $header = $this->pageData['name'];
        }
        return $header;
    }
}
