<?php
namespace Shop\Structure\Good\Site;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Field\Url;

class ModelAbstract extends \Ideal\Core\Site\Model
{
    /** @var  категория для которой нужно отобразить список товаров */
    protected $category;

    public function setCategory($category)
    {
        $this->category = $category;
    }


    public function getWhere($where)
    {
        if (isset($this->fields['category_id'])) {
            // Для случая, когда товар привязан к одной категории
            $where = "WHERE {$where} AND e.category_id={$this->category['ID']}";
        } else {
            // Для случая, когда товар привязан к разным категориям
            $config = Config::getInstance();
            $prefix = $config->db['prefix'];
            $where = " LEFT JOIN {$prefix}shop_category_good AS cg ON cg.good_id = e.ID
                    WHERE {$where} AND cg.category_id = '{$this->category['ID']}'";
        }
        $where .= ' AND e.is_active=1';

        return $where;
    }


    public function detectPageByUrl($path, $url)
    {
        if(count($url) == 0) return '404';
        $db = Db::getInstance();
        if (count($url) > 1) {
            $tmp = '';
            foreach ($url as $k => $v) {
                if($k != 1) $tmp .= ' OR ';
                $tmp .= 'url="'.$v.'"';
            }
            $url = '('.$tmp.')';
        } else {
            $url = $url[0];
        }

        $url = $db->escape_string($url);
        $_sql = "SELECT * FROM {$this->_table} WHERE url='{$url}' LIMIT 1";

        $list = $db->select($_sql); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($list[0]['ID'])) {
            return '404';
        }
        $list[0]['structure'] = 'Shop_Good';

        $this->path = array_merge($path, $list);
        $this->pageData = end($list);

        return $this;
    }


    public function getStructureElements()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $urlModel = new Url\Model();

        $_sql = "SELECT * FROM {$this->_table} WHERE is_active=1 ORDER BY cid";
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

}
