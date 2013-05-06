<?php
namespace Shop\Structure\Good\Site;

use Ideal\Core\Db;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{

    public function detectPageByUrl($url)
    {
        $db = Db::getInstance();

        $_sql = "SELECT * FROM {$this->_table} WHERE url='{$url}' LIMIT 1";

        $list = $db->queryArray($_sql); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($list[0]['ID'])) {
            return '404';
        }
        $list[0]['structure'] = 'Shop_Good';

        $this->path = $list;
        $this->object = end($list);

        return array();
    }


    public function getTitle()
    {
        if (isset($this->object['title']) AND $this->object['title'] != '') {
            return $this->object['title'];
        } else {
            return $this->object['name'];
        }
    }

}
