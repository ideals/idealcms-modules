<?php
namespace MiniForum\Structure\Post\Site;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Request;
use Ideal\Core\Util;

class Model extends \Ideal\Core\Site\Model
{
    public $cid;
    protected $post;
    protected $where;
    protected $pageStructure = false;
    protected $structurePath = '1-31';

      public function getWhere($where)
      {
          $where = 'WHERE ' . $where . $this->where . ' AND is_active=1 AND parent_id=0';
          return $where;
      }

    public function setWhere($where)
    {
        $this->where = $where;
    }

    /**
    * @param int $page Номер отображаемой страницы
    * @return array Полученный список элементов
    */
    public function getList($page)
    {
        $config = Config::getInstance();

        //Изменяем сортировку, так как новые сообщения должны быть в начале
        $this->params['field_sort'] .= ' DESC';

        $posts = parent::getList($page);
        $db = Db::getInstance();

        foreach ($posts as $k => $v) {
            $posts[$k]['link'] = '/forum' . '/' . $v['ID'] . $config->urlSuffix;
            $posts[$k]['date_create'] = Util::dateReach($v['date_create']);

            //Резделяем текст в соответствии с условиями
            $text = $this->splitSimbols($posts[$k]['content'], 30, 200);

            $posts[$k]['firstText'] = $text[0];
            $posts[$k]['secondText'] = $text[1];

            if ((mb_strlen($posts[$k]['firstText'] . $posts[$k]['secondText']) < mb_strlen($v['content'])) && ($posts[$k]['secondText'] !== '')) {
                $posts[$k]['secondText'] .= '...';
            }

            $posts[$k]['firstText']  = str_replace('\r\n', ' ', $posts[$k]['firstText']);
            $posts[$k]['secondText']  = str_replace('\r\n', ' ', $posts[$k]['secondText']);

            $_sql = "SELECT COUNT(*) FROM {$this->_table} WHERE main_parent_id='{$v['ID']}'";
            $answerCount = $db->queryArray($_sql);
            $posts[$k]['answer_count'] = $answerCount[0]['COUNT(*)'];
        }
        return $posts;
    }


    public function detectPageByUrl($url, $path)
    {
        $db = Db::getInstance();

        $url = mysql_real_escape_string($url[0]);
        $_sql = "SELECT * FROM {$this->_table} WHERE is_active=1 AND id='{$url}'";
        $post = $db->queryArray($_sql); // запрос на получение всех страниц, соответствующих частям url


        // Страницу не нашли, возвращаем 404
        if (!isset($post[0]['ID'])) {
            return '404';
        }

        $post[0]['structure'] = 'MiniForum_Post';
        $post[0]['url'] = $url;
        //$post[0]['content'] = str_replace('\r\n', '<br />', $post[0]['content']);
        $post[0]['content'] = nl2br($post[0]['content']);
        $post[0]['date_create'] = Util::dateReach($post[0]['date_create']);

        $this->path = array_merge($path, $post);

        $request = new Request();
        $request->action = 'detail';

        return array();
    }

/*
    public function getHeader($header = '')
    {
        if ($header == '') {
            $header = end($this->path);
            $header = $header['content'];
            //Резделяем текст в соответствии с условиями
            $header = $this->splitSimbols($header, 30, 100);
        }
        return $header[0];
    }
*/

    public function setTitle($title = '')
        {
            if ($title == '') {
                $content = end($this->path);
                $content = $content['content'];
                $content = strip_tags($content);
                $content = Util::smartTrim($content, 50);
                $this->object['title'] = 'Онкология форум - ' . $content;
            } else {
                $this->object['title'] = $title;
            }
            return $title;
        }

    public function splitSimbols($text, $min, $max)
    {
        $str = mb_substr($text, $min);
        preg_match_all('/(.*)[\.\?\!]/U', $str, $second, PREG_OFFSET_CAPTURE);
        $second = $second['0']['0']['0'];

        if ($second) {
            $rText[0] = mb_substr($text, 0, $min) . $second;
        } else {
            $rText[0] = $text;
        }
        $textSize = mb_strlen($rText[0]);

        $rText[1] = '';
        if ($textSize < mb_strlen($text)) {
            if ($max !== 0) {
                $rText[1] = mb_substr($text, $textSize, $max - $textSize);
                $rText[1] = Util::smartTrim($rText[1] , $max - $textSize - 1);
            } else {
                $rText[1] = mb_substr($text, $textSize);
            }
        }

        return $rText;
    }

    public function setDescription($description = '')
    {
        if ($description == '') {
            $this->object['description'] = Util::smartTrim($this->object['content'], 170);
        } else {
            $this->object['description'] = $description;
        }
    }

    public function setKeywords($keywords = '')
    {
        if ($keywords == '') {
            $this->object['keywords'] = Util::smartTrim($this->object['content'], 200);
        } else {
            $this->object['keywords'] = $keywords;
        }
    }


    public function getChildPosts()
    {
        $db = Db::getInstance();
        $root = $this->object['ID'];

        /*$pageStructure = $this->pageStructure;
        $where = '';
        if ($pageStructure) {
            $where = "AND page_structure = '{$pageStructure}'";
        }*/

        $_sql = "SELECT * FROM {$this->_table} WHERE is_active=1 AND main_parent_id='{$root}' {$where}";
        $childPosts = $db->queryArray($_sql);

        $childPosts = $this->buildTree($childPosts, $root);
        $childPosts = $this->buildList($childPosts);

        foreach ($childPosts as $k => $post) {
            $childPosts[$k]['margin'] = $post['indent'] * 50;
            $childPosts[$k]['content'] = nl2br($childPosts[$k]['content']);
            $childPosts[$k]['date_create'] = Util::dateReach($childPosts[$k]['date_create']);
        }

        return $childPosts;
    }



    public function getPost($ID)
    {
        $db = Db::getInstance();

        $_sql = "SELECT * FROM {$this->_table} WHERE ID = {$ID}";
        $post = $db->queryArray($_sql);

        return $post;
    }



    public function  setStructurePath($pageStructure) {
        $this->pageStructure = $pageStructure;
    }


    function buildTree(&$list, $root)
   {
       $tree = array();
       foreach ($list as $k => $v) {
           if ($v['parent_id'] == $root) {
               //unset($list[$k]);
               $v['elements'] = $this->buildTree($list, $v['ID']);
               $tree[] = $v;
           }
       }
       return $tree;
   }


   function buildList($tree, $indent = 0)
   {
       $list = array();
       foreach ($tree as $k => $v) {
           $v['indent'] = $indent;
           $newList = $this->buildList($v['elements'], $indent+1);
           unset($v['elements']);
           $list[] = $v;
           $list = array_merge($list, $newList);
       }
       return $list;
   }

   public function setPost($post) {
       $this->post = $post;
   }

   public function addNewPost() {
       $db = Db::getInstance();
       $time = time();

       if ((isset($this->post['is_poster']) && $this->post['is_poster'] == 'true') || ($this->post['email'] == 'zzz@zzz.zz')) {
           if ($this->post['main_parent_id'] !== '0') { // Если добавляется ответ
       					$_sql = "SELECT date_create FROM $this->_table WHERE parent_id = {$this->post['parent_id']}  ORDER BY date_create";
       					$dates = $db->queryArray($_sql);

       					if (count($dates) > 0) { // Если на родительское сообщение уже были ответы
       					    $date = end($dates);
       					    $date_create = $date['date_create'];

       					} else {
       						$_sql = "SELECT date_create FROM $this->_table WHERE ID = {$this->post['parent_id']}";
       						$date = $db->queryArray($_sql);
       						$date_create = $date[0]['date_create'];
       					}

       					$threeWeek = 21 * 24 * 60 * 60; // 21 days; 24 hours; 60 mins; 60secs
                        $time = mt_rand($date_create, $date_create + $threeWeek);

       				} else {	// Если создается тема
                        $time = $par['date_create'] = mt_rand(mktime(0,0,0,11,1,2012), mktime(0,0,0,10,31,2013));
       				}
       }
       $values['structure_path'] = $this->structurePath;
       $values['parent_id'] = $this->post['parent_id'];
       $values['main_parent_id'] = $this->post['main_parent_id'];
       $values['page_structure'] = $this->post['page_structure'];
       $values['author'] = $this->post['author'];
       $values['email'] = $this->post['email'];
       $values['content'] = $this->post['content'];
       $values['date_create'] = $time;
       $values['is_active'] = 1;

       $result = $db->insert($this->_table, $values);

       return $result; //ID нового ответа || false
   }

    public function updatePost() {
        $db = Db::getInstance();
        foreach ($this->post as $k => $v) {
            $this->post[$k] = mysql_real_escape_string($v);
        }
        $_sql = "UPDATE $this->_table SET author = '{$this->post['author']}', email = '{$this->post['email']}', content = '{$this->post['content']}' WHERE ID = {$this->post['ID']}";

        $result = $db->query($_sql);
        return $result; //true || false
    }

   public function deletePost() {

       $db = Db::getInstance();
       $_sql = "DELETE FROM $this->_table WHERE ID = {$this->post['ID']} ";
       $result = $db->query($_sql);

       if ($this->post['main_parent_id'] == 0) {
           // Это первое сообщение, удаляем всё подчистую
           $_sql = "DELETE FROM $this->_table WHERE main_parent_id = {$this->post['ID']} ";
           $db->query($_sql);

       } else {
           // Заменяем у всех потомков родительский id
           $_sql = "UPDATE $this->_table SET parent_id={$this->post['parent_id']} WHERE parent_id={$this->post['ID']}";
           $db->query($_sql);
       }
       return $result; //true || false
   }

}