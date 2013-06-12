<?php
/**
 * Виджет, отображающий содержимое корзины
 */

namespace Shop\Structure\Basket\Widget;

use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Core\Util;

class Basket extends \Ideal\Core\Widget
{

    public function getData()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();

        // Считываем список новостей
        $table = $config->db['prefix'] . 'ideal_structure_news';
        $_sql = 'SELECT ID, name, annot, date_create, img, url
                 FROM ' . $table . '
                 WHERE is_active=1
                 ORDER BY date_create DESC
                 LIMIT ' . intval($num);
        $news = $db->queryArray($_sql);
        $freshNews = array();
        $num = 0;
        foreach ($news as $v) {
            $freshNews[$num]['name'] = $v['name'];
            $freshNews[$num]['url'] = $v['url'];
            $freshNews[$num]['img'] = $v['img'];
            $freshNews[$num]['annot'] = $v['annot'];
            $freshNews[$num]['date'] = Util::dateReach($v['date_create']);
            $num++;
        }

        return $freshNews;        
    }

}