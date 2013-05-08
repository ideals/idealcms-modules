<?php
namespace Articles\Structure\Category\Site;

use Ideal\Core\Db;
use Ideal\Field;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{
    public function getArticles()
    {
        $db = Db::getInstance();
        if ($this->object['structure_path'] === "1" ) {
            // Запрос для отображения всех статей
            $_sql = "SELECT * FROM i_articles_structure_paper WHERE is_active = 1";

        } else {
            // Вывод статей только определённой категории
            $categoryId = $this->object['ID'];
            $_sql = "SELECT paper FROM i_articles_paper WHERE articles='{$categoryId}'";
            $goodIdsArr = $db->queryArray($_sql);
            if (count($goodIdsArr) == 0) {
                return array();
            }
            $goodIs = array();
            foreach ($goodIdsArr as $good) {
                $goodIs[] = "'" . $good['paper'] . "'";
            }
            $goodIs = implode(',', $goodIs);

            $_sql = "SELECT * FROM i_articles_structure_paper WHERE is_active=1 AND ID IN ({$goodIs}) ORDER BY name";
        }
        $goods = $db->queryArray($_sql);
        return $goods;
    }

    public function getList()
    {
        $db = Db::getInstance();
        $_sql = "SELECT * FROM i_articles_structure_category WHERE structure_path='{$this->structurePath}'";
        //return $db->queryArray($_sql);
        $list = $db->queryArray($_sql);
        foreach ($list as $k => $v) {
            $list[$k]['url_full'] = "/article/".$list[$k]['url'];
        }

        return $list;
    }
}