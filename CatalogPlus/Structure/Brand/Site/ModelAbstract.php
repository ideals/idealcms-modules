<?php
namespace CatalogPlus\Structure\Brand\Site;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Request;
use Ideal\Structure\Part;

class ModelAbstract extends Part\Site\Model
{

    public function getWhere($where)
    {
        if ($where != '') {
            $where .= " AND ";
        }
        $where = 'WHERE ' . $where . ' is_active=1';

        return $where;
    }

    /**
     * Получить общее количество элементов в списке
     * @return array Полученный список элементов
     */
    public function getListCount()
    {
        $db = Db::getInstance();
        $request = new Request();
        if ($request->action == 'detail') {
            $config = Config::getInstance();
            $where = $this->getWhere("e.brand_id='{$this->pageData['ID']}'");
            $goodTable = $config->db['prefix'] . 'catalogplus_structure_good';
            $_sql = " SELECT COUNT(e.ID) FROM {$goodTable} AS e {$where}";
        } else {
            $where = $this->getWhere("e.prev_structure='{$this->prevStructure}'");
            // Считываем все элементы первого уровня
            $_sql = "SELECT COUNT(e.ID) FROM {$this->_table} AS e {$where}";
        }
        $list = $db->select($_sql);

        return $list[0]['COUNT(e.ID)'];
    }

    public function detectPageByUrl($path, $url)
    {
        $db = Db::getInstance();

        $url = $db->real_escape_string(end($url));
        $sql = "SELECT * FROM {$this->_table} WHERE is_active=1 AND url='{$url}'  AND date_create < " . time();

        $brand = $db->select($sql); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($brand[0]['ID'])) {
            return '404';
        }

        $brand[0]['structure'] = 'CatalogPlus_Brand';
        $brand[0]['url'] = $url;

        $this->path = array_merge($path, $brand);

        $request = new Request();
        $request->action = 'detail';
        return $this;
    }

    public function getGoods()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $goodTable = $config->db['prefix'] . 'catalogplus_structure_good';
        $idBrand = $this->pageData['ID'];
        $sql = "SELECT * FROM {$goodTable} WHERE brand_id = '{$idBrand}' AND is_active=1";
        $request = new Request();
        $page = intval($request->page);
        if (is_null($page)) {
            $this->setPageNum($page);
        } else {
            // Определяем кол-во отображаемых элементов на основании названия класса
            $class = strtolower(get_class($this));
            $class = explode('\\', trim($class, '\\'));
            $nameParam = ($class[3] == 'admin') ? 'elements_cms' : 'elements_site';
            $onPage = $this->params[$nameParam];

            $page = $this->setPageNum($page);
            $start = ($page - 1) * $onPage;

            $sql .= " LIMIT {$start}, {$onPage}";
        }
        return $db->select($sql);
    }



    public function getHeader()
    {
        $header = '';
        if (isset($this->pageData['annot'])) {
            // Если есть шаблон с контентом, пытаемся из него извлечь заголовок H1
            list($header, $text) = $this->extractHeader($this->pageData['annot']);
            $this->pageData['annot'] = $text;
        }

        if ($header == '') {
            // Если заголовка H1 в тексте нет, берём его из названия name
            $header = $this->pageData['name'];
        }
        return $header;
    }

    public function setObjectNew()
    {

    }

    public function getStructureElements()
    {
        $list = $this->getList(0, 9999);
        return $list;
    }
}
