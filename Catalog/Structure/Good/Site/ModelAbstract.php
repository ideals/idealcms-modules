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
        if (count($url) > 1) {
            $this->is404 = true;
            return $this;
        }

        $db = Db::getInstance();

        $_sql = "SELECT * FROM {$this->_table} WHERE url=:url LIMIT 1";
        $par = array('url' => $url[0]);

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
        $config = Config::getInstance();
        $urlModel = new Url\Model();

        $_sql = "SELECT * FROM {$this->_table} WHERE is_active=1 ORDER BY name";
        $list = $db->select($_sql);

        $lvl = 0;
        $url = array('0' => array('url' => $config->structures[0]['url']));
        foreach ($list as $k => $v) {
            if ($v['lvl'] > $lvl) {
                if ($v['url'] != '/') {
                    $url[] = $list[$k - 1];
                }
                $urlModel->setParentUrl($url);
            } elseif ($v['lvl'] < $lvl) {
                // Если двойной или тройной выход добавляем соответствующий мультипликатор
                $c = $lvl - $v['lvl'];
                $url = array_slice($url, 0, -$c);
                $urlModel->setParentUrl($url);
            }
            $lvl = $v['lvl'];
            $list[$k]['url'] = $urlModel->getUrl($v);
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
}
