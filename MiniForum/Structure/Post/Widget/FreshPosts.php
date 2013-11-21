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
        $table = $config->db['prefix'] . 'miniforum_structure_post';

        //получаем только корневые и активные сообщения форума
        $where = array('is_active' => '1', 'main_parent_id' => '0');
        $posts = $db->select($table, $where, 'date_create DESC', 'is_active', 3);

        foreach ($posts as $key => $value) {
            $posts[$key]['date_create'] = Util::dateReach($value['date_create']);
            $value['content'] = strip_tags($value['content']);

            $posts[$key]['content'] = $value['content'];

            //Получаем заголовок сообщения отделением его от основного сообщения
            $str = mb_substr($value['content'], 30);
            preg_match_all('/(.*)[\.\?\!]/U', $str, $content, PREG_OFFSET_CAPTURE);
            $content = $content['0']['0']['0'];

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

            if (((mb_strlen($posts[$key]['after_link']) + $contentSize) < mb_strlen($value['content'])) && (mb_strlen($posts[$key]['after_link']) > 0)) {
                $posts[$key]['after_link'] .= '...';
            }

            $id = $value['main_parent_id'] == 0 ? $value['ID'] : $value['main_parent_id'];
            $posts[$key]['link'] = '/forum' . '/' . $id . $config->urlSuffix;

        }


        return $posts;
    }
}
