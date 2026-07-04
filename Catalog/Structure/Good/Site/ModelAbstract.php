<?php

namespace Catalog\Structure\Good\Site;

use Ideal\Core\Site\Model;
use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Field\Url;

class ModelAbstract extends Model
{
    protected $categoryModel;

    public function setCategoryModel($categoryModel): void
    {
        $this->categoryModel = $categoryModel;
    }

    public function detectPageByUrl($path, $url): Model
    {
        if (count($url) === 0) {
            $this->is404 = true;
            return $this;
        }

        $db = Db::getInstance();
        $config = Config::getInstance();
        $end = end($path);
        $prevStructure = $config->getStructureByName($this->params['in_structures'][0]);

        $_sql = sprintf('SELECT * FROM %s WHERE BINARY url=:url AND prev_structure=:prev_structure LIMIT 1', $this->_table);
        $par = ['url' => $url[0], 'prev_structure' => $prevStructure['ID'] . '-' . $end['ID']];

        $list = $db->select($_sql, $par); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($list[0]['ID'])) {
            $this->path = $path;
            $this->is404 = true;
            return $this;
        }

        $list[0]['structure'] = 'Catalog_Good';

        $this->path = array_merge($path, $list);

        $_REQUEST['action'] = 'detail'; // раз до сюда добрались - это подробное описание товара

        return $this;
    }

    public function getList($page = null)
    {
        $list = parent::getList($page);

        // Построение правильных URL
        $url = new \Ideal\Field\Url\Model();
        $url->setParentUrl($this->path);
        if (is_array($list) && ($list !== [])) {
            foreach ($list as $k => $v) {
                $list[$k]['link'] = $url->getUrl($v);
            }
        }

        return $list;
    }


    public function getStructureElements()
    {
        $db = Db::getInstance();
        $ps = $this->prevStructure; // todo вот здесь я не уверен, что это правильная prev_structutre

        $_sql = sprintf("SELECT * FROM %s WHERE is_active=1 AND prev_strucutre='%s' ORDER BY name", $this->_table, $ps);
        $list = $db->select($_sql);

        // Построение ссылок на товар
        $urlModel = new Url\Model();
        $urlModel->setParentUrl($this->path);

        foreach ($list as $k => $v) {
            $list[$k]['link'] = $urlModel->getUrl($v);
        }

        return $list;
    }


    public function detectPath()
    {
        $good = $this->getPageData();

        $category = new \Catalog\Structure\Category\Site\Model('');
        $categoryId = explode('-', $good['prev_structure']);
        $categoryId = end($categoryId);

        $category->setPageDataById($categoryId);

        return $category->detectPath();
    }

    /**
     * Получение информации о товарах
     *
     * @param array $ids список id товаров
     * @return array информация о товарах
     * @throws \Exception
     */
    public function getGoodsInfo($ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $in = '(' . implode(',', $ids) . ')';
        $db = Db::getInstance();
        $_sql = sprintf('SELECT * FROM %s WHERE ID IN %s', $this->_table, $in);
        $goods = $db->select($_sql);

        return array_column($goods, null, 'ID');
    }

    protected function getWhere($where)
    {
        $config = Config::getInstance();
        $endPart = end($this->path);
        $structure = $config->getStructureByName($endPart['structure']);
        if (!is_null($this->categoryModel)) {
            $cid = $this->categoryModel->getCidSegment();
            $where = sprintf("e.prev_structure IN (SELECT CONCAT('%s-', ID) FROM i_catalog_structure_category WHERE cid LIKE '%s%%')", $structure['ID'], $cid);
        }

        return parent::getWhere($where . ' AND e.is_active=1');
    }
}
