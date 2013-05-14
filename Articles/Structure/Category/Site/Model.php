<?php
namespace Articles\Structure\Category\Site;

use Ideal\Core\Db;
use Ideal\Field;

class Model extends \Ideal\Structure\Part\Site\ModelAbstract
{
    public function getArticles($row, $page)
    {
        $db = Db::getInstance();
        $arr = array();
        $_sql = "SELECT COUNT(*) AS `counter` FROM `i_articles_structure_paper`";
        $count = $db->queryArray($_sql);

        $elements = $count[0]['counter'];
        if(empty($elements)){
            $arr['list'] = '<a class="active">1</a>';
            return $arr;
        }

        $pages = ceil($elements / $row);
        if ($page < 1) {
            $page = 1;
        } elseif ($page > $pages) {
            $page = $pages;
        }


        $start = ($page - 1) * $row;
        if ($this->object['structure_path'] === "1") {
            // Запрос для отображения всех статей
            $_sql = "SELECT * FROM i_articles_structure_paper WHERE is_active = 1 LIMIT {$start}, {$row}";

        } else {
            // Вывод статей только определённой категории
            $categoryId = $this->object['ID'];
            $_sql = "SELECT paper FROM i_articles_paper WHERE articles='{$categoryId}'LIMIT {$start}, {$row}";
            $goodIdsArr = $db->queryArray($_sql);
            if (count($goodIdsArr) == 0) {
                $arr['list'] = '<a class="active">1</a>';
                return $arr;
            }
            $goodIs = array();
            foreach ($goodIdsArr as $good) {
                $goodIs[] = "'" . $good['paper'] . "'";
            }
            $goodIs = implode(',', $goodIs);

            $_sql = "SELECT * FROM i_articles_structure_paper WHERE is_active=1 AND ID IN ({$goodIs}) ORDER BY name";
        }
        $arr['paper'] = $db->queryArray($_sql);

        $neighbours = 6; // Количество ячеек отображаемых для навигации по страницам
        $left_neighbour = $page - $neighbours;
        if ($left_neighbour < 1) $left_neighbour = 1;

        $right_neighbour = $page + $neighbours;
        if ($right_neighbour > $pages) $right_neighbour = $pages;

        $arr['list'] = "";

        for ($i = $left_neighbour; $i <= $right_neighbour; $i++) {
            if ($i != $page) {
                $arr['list'] .= ' <a href="?page=' . $i . '">' . $i . '</a> ';
            } else {
                // выбранная страница
                $arr['list'] .= ' <a class="active">' . $i . '</a> ';
            }
        }

        return $arr;
    }

    public function getList()
    {
        $db = Db::getInstance();
        $_sql = "SELECT * FROM i_articles_structure_category WHERE structure_path='{$this->structurePath}'";
        //return $db->queryArray($_sql);
        $list = $db->queryArray($_sql);
        foreach ($list as $k => $v) {
            $list[$k]['url_full'] = "/article/" . $list[$k]['url'];
        }

        return $list;
    }

}