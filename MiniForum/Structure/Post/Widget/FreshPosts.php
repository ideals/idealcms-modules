<?php
/**
 * Вывод последних сообщений на главную страницу
 */
namespace MiniForum\Structure\Post\Widget;

use Ideal\Core\Util;
use Ideal\Core\Db;
use Ideal\Core\Config;
use Ideal\Field;

class FreshPosts extends \Ideal\Core\Widget
{
    public function getData() {
        $db = Db::getInstance();
        $config = Config::getInstance();

        //получаем только корневые и активные сообщения форума
        $_sql = "
          SELECT 
            * 
          FROM 
            &table 
          WHERE 
            is_active = :is_active AND 
            main_parent_id = :main_parent_id 
          ORDER BY date_create DESC 
          LIMIT 3";
        $params = array('is_active' => '1', 'main_parent_id' => '0');
        $fields = array('table' => $config->db['prefix'] . 'miniforum_structure_post');
        $posts = $db->select($_sql, $params, $fields);


        foreach ($posts as $key => $value) {
            $posts[$key]['date_create'] = Util::dateReach($value['date_create']);
            $value['content'] = strip_tags($value['content']);

            $posts[$key]['content'] = $value['content'];

            //Получаем заголовок сообщения отделением его от основного сообщения
            $str = mb_substr($value['content'], 30);
            preg_match_all('/(.*)[\.\?\!]/U', $str, $content, PREG_OFFSET_CAPTURE);
            if (isset($content['0']) && isset($content['0']['0']) && isset($content['0']['0']['0'])) {
                $content = $content['0']['0']['0'];
            } else {
                $content = '';
            }

            if ($content) {
                $posts[$key]['content'] = mb_substr($value['content'], 0, 30) . $content;
            } else {
                $posts[$key]['content'] = $value['content'];
             }
            $contentSize = mb_strlen($posts[$key]['content']);

            if ($contentSize < mb_strlen($value['content'])) {
                $posts[$key]['after_link'] = mb_substr($value['content'], $contentSize, 200 - $contentSize);
                $posts[$key]['after_link'] = Util::smartTrim($posts[$key]['after_link'] , 200 - $contentSize - 1);
            }

            if (
                isset($posts[$key]['after_link']) &&
                ((mb_strlen($posts[$key]['after_link']) + $contentSize) < mb_strlen($value['content'])) &&
                (mb_strlen($posts[$key]['after_link']) > 0)
            ) {
                $posts[$key]['after_link'] .= '...';
            }

            $id = $value['main_parent_id'] == 0 ? $value['ID'] : $value['main_parent_id'];
            $posts[$key]['link'] = '/forum' . '/' . $id . $config->urlSuffix;

        }


        return $posts;
    }
}
