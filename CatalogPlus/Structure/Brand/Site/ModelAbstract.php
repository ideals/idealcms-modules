<?php

namespace CatalogPlus\Structure\Brand\Site;

use Ideal\Structure\Part\Site\Model;
use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Request;

class ModelAbstract extends Model
{
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
            $where = $this->getWhere(sprintf("e.brand_id='%s'", $this->pageData['ID']));
            $goodTable = $config->db['prefix'] . 'catalogplus_structure_good';
            $_sql = sprintf(' SELECT COUNT(e.ID) FROM %s AS e %s', $goodTable, $where);
        } else {
            $where = $this->getWhere(sprintf("e.prev_structure='%s'", $this->prevStructure));
            // Считываем все элементы первого уровня
            $_sql = sprintf('SELECT COUNT(e.ID) FROM %s AS e %s', $this->_table, $where);
        }

        $list = $db->select($_sql);

        return $list[0]['COUNT(e.ID)'];
    }

    public function detectPageByUrl($path, $url): \Ideal\Core\Site\Model
    {
        $db = Db::getInstance();
        if (count($url) > 1) {
            $this->is404 = true;
            return $this;
        }

        $url = $db->real_escape_string(end($url));
        $sql = sprintf("SELECT * FROM %s WHERE is_active=1 AND url='%s'  AND date_create < ", $this->_table, $url) . time();

        $brand = $db->select($sql); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($brand[0]['ID'])) {
            $this->is404 = true;
            return $this;
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
        $sql = sprintf("SELECT * FROM %s WHERE brand_id = '%s' AND is_active=1", $goodTable, $idBrand);
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

            $sql .= sprintf(' LIMIT %s, %s', $start, $onPage);
        }

        $goods = $db->select($sql);
        foreach ($goods as $k => $v) {
            $goods[$k]['properties'] = unserialize($v['properties']);
            if (isset($goods[$k]['properties']['Состав'])) {
                // Перенос поля состов в конец списка свойст
                $tmp = $goods[$k]['properties']['Состав'];
                unset($goods[$k]['properties']['Состав']);
                $goods[$k]['properties']['Состав'] = $tmp;
            }

            $goods[$k]['name'] = str_ireplace($goods[$k]['brand'], '', $goods[$k]['name']);

            if (isset($v['sell']) && $v['sell'] != null && time() < $v['sell_date']) {
                $goods[$k]['oldPrice'] = $v['price'];
                $goods[$k]['price'] = $v['price'] - floor($v['price'] / 100 * $v['sell']);
            }

            $goods[$k]['link'] = 'href="/shop/detail/' . $v['url'] . $config->urlSuffix . '"';
        }

        return $goods;
    }



    public function getHeader()
    {
        $header = '';
        if (isset($this->pageData['annot'])) {
            // Если есть шаблон с контентом, пытаемся из него извлечь заголовок H1
            [$header, $text] = $this->extractHeader($this->pageData['annot']);
            $this->pageData['annot'] = $text;
        }

        if ($header == '') {
            // Если заголовка H1 в тексте нет, берём его из названия name
            $header = $this->pageData['name'];
        }

        return $header;
    }

    public function setObjectNew() {}

    public function getStructureElements()
    {
        return $this->getList(0);
    }

    protected function getWhere($where): string
    {
        if ($where != '') {
            $where .= " AND ";
        }

        return 'WHERE ' . $where . ' is_active=1';
    }
}
