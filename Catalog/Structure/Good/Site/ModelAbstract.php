<?php
namespace Catalog\Structure\Good\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Field\Url;

class ModelAbstract extends \Ideal\Core\Site\Model
{
    protected $categoryModel = null;

    public function setCategoryModel($categoryModel)
    {
        $this->categoryModel = $categoryModel;
    }

    public function getWhere($where)
    {
        $config = Config::getInstance();
        $endPart = end($this->path);
        $structure = $config->getStructureByName($endPart['structure']);
        if (!is_null($this->categoryModel)) {
            $cid = $this->categoryModel->getCidSegment();
            $where = "e.prev_structure IN (SELECT CONCAT('{$structure['ID']}-', ID) FROM i_catalog_structure_category WHERE cid LIKE '{$cid}%')";
        }

        $where = parent::getWhere($where . ' AND e.is_active=1');
        return $where;
    }

    public function detectPageByUrl($path, $url)
    {
        if (count($url) == 0) {
            $this->is404 = true;
            return $this;
        }
        if (count($url) > 1) {
            $this->is404 = true;
            return $this;
        }

        $db = Db::getInstance();
        $config = Config::getInstance();
        $end = end($path);
        $prevStructure = $config->getStructureByName($this->params['in_structures'][0]);

        $_sql = "SELECT * FROM {$this->_table} WHERE BINARY url=:url AND prev_structure=:prev_structure LIMIT 1";
        $par = array('url' => $url[0], 'prev_structure' => $prevStructure['ID'] . '-' . $end['ID']);

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
        if (is_array($list) && (count($list) != 0)) {
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

        $_sql = "SELECT * FROM {$this->_table} WHERE is_active=1 AND prev_strucutre='{$ps}' ORDER BY name";
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
        $category_id = explode('-', $good['prev_structure']);
        $category_id = end($category_id);
        $category->setPageDataById($category_id);
        $path = $category->detectPath();

        return $path;
    }

    /**
     * Получение информации о товарах
     *
     * @param array $ids список id товаров
     * @return array информация о товарах
     * @throws \Exception
     */
    public function getGoodsInfo($ids)
    {
        $goods = array();
        if (!empty($ids)) {
            $in = '(' . implode(',', $ids) . ')';
            $db = Db::getInstance();
            $_sql = "SELECT * FROM {$this->_table} WHERE ID IN {$in}";
            $goodsFromBase = $db->select($_sql);
            array_walk($goodsFromBase, function ($v) use (&$goods) {
                $goods[$v['ID']] = $v;
            });
        }
        return $goods;
    }
}
