<?php
namespace Shop\Structure\Good\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Field\Url;

class Model extends \Ideal\Core\Site\Model
{
    /** @var  ID категории для которой нужно отобразить список товаров */
    protected $categoryId;

    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
    }


    public function getWhere($where)
    {
        if (isset($this->fields['category_id'])) {
            // Для случая, когда товар привязан к одной категории
            $where = "WHERE {$where} AND category_id={$this->categoryId}";
        } else {
            // Для случая, когда товар привязан к разным категориям
            $config = Config::getInstance();
            $prefix = $config->db['prefix'];
            $where = " LEFT JOIN {$prefix}shop_category_good AS cg ON cg.good_id = e.ID
                    WHERE {$where} AND cg.category_id = '{$this->categoryId}'";
        }
        $where .= ' AND is_active=1';

        return $where;
    }


    public function detectPageByUrl($url, $path)
    {
        $db = Db::getInstance();

        $_sql = "SELECT * FROM {$this->_table} WHERE url='{$url}' LIMIT 1";

        $list = $db->queryArray($_sql); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($list[0]['ID'])) {
            return '404';
        }
        $list[0]['structure'] = 'Shop_Good';

        $this->path = array_merge($path, $list);
        $this->object = end($list);

        return array();
    }


    public function getStructureElements()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $urlModel = new Url\Model();

        $_sql = "SELECT * FROM {$this->_table} WHERE is_active=1 ORDER BY cid";
        $list = $db->queryArray($_sql);

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

}
