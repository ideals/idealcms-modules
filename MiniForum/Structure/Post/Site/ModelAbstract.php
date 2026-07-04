<?php

namespace MiniForum\Structure\Post\Site;

use Ideal\Core\Site\Model;
use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Request;
use Ideal\Core\Util;
use Ideal\Mailer;
use Ideal\Structure\User;

class ModelAbstract extends Model
{
    public $cid;

    protected $post;

    protected $where;

    protected $pageStructure = false;

    protected $prevStructure;

    protected $parentUrl = '/forum';

    protected $isModerator = false;

    public function __construct($prevStructure)
    {
        parent::__construct($prevStructure);

        // Для авторизованного пользователя выводим все посты
        $user = new User\Model();
        if (!isset($user->data['ID'])) {
            $this->where = 'AND is_moderated=1';
        } else {
            $this->isModerator = true;
        }
    }

    /**
     * @return mixed[]
     */
    public function getComments($pageStructure): array
    {
        // todo сделать ограничение на количество комментариев на странице
        // Очень выжно чтобы главные посты шли в конце иначе при получении дочерних постов через getCommentsTree,
        // мы не получим все элементы
        $_sql = "SELECT * FROM {$this->_table}
                    WHERE is_active=1 {$this->where} AND
                    (
                    page_structure=:ps OR
                    (main_parent_id IN (SELECT ID FROM {$this->_table} WHERE page_structure=:ps) )
                    )
                    ORDER BY main_parent_id DESC, date_create ASC";
        $params = ['ps' => $pageStructure];
        $db = Db::getInstance();
        $list = $db->select($_sql, $params);
        $posts = [];
        foreach ($list as &$v) {
            $v['date_create'] = Util::dateReach($v['date_create']) . ' ' . date('G:i', $v['date_create']);
            $v['content'] = nl2br($v['content']);
            if ($v['main_parent_id'] != 0) {
                continue;
            }

            $element = $v;
            $element['margin'] = 0;
            $element['elements'] = $this->buildTree($list, $v['ID']);
            $element['elements'] = $this->buildList($element['elements'], 1);
            $posts = array_merge($this->buildChildrenPosts(50, ['0' => $element]), $posts);
        }


        return $posts;
    }

    /**
     * @param int $page Номер отображаемой страницы
     * @return array Полученный список элементов
     */
    public function getList($page = null)
    {
        $posts = parent::getList($page);

        foreach ($posts as $k => $v) {
            $posts[$k] = $this->parsePost($v);
            $posts[$k]['answer_count'] = $this->getAnswerCount($v);
        }

        return $posts;
    }

    public function detectPageByUrl($path, $url): Model
    {
        // Условие отбора
        $where = '';
        $db = Db::getInstance();

        $_sql = sprintf('SELECT * FROM %s WHERE is_active=1 AND ID=:url %s', $this->_table, $where);
        $params = ['url' => $url[0]];
        $post = $db->select($_sql, $params); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($post[0]['ID'])) {
            $this->is404 = true;
            return $this;
        }

        $post[0]['structure'] = 'MiniForum_Post';
        $post[0]['url'] = $url[0];
        //$post[0]['content'] = str_replace('\r\n', '<br />', $post[0]['content']);
        $post[0]['content'] = nl2br($post[0]['content']);
        $post[0]['date_create'] = Util::dateReach($post[0]['date_create']);

        $this->path = array_merge($path, $post);

        $request = new Request();
        $request->action = 'detail';

        return $this;
    }

    public function setTitle($title = ''): string
    {
        if ($title == '') {
            $tmp = count($this->path) - 2;
            $content = end($this->path);
            $content = $content['content'];
            $content = strip_tags($content);
            $content = Util::smartTrim($content, 50);
            $this->pageData['title'] = $this->path[$tmp]['name'] . ' - ' . $content;
        } else {
            $this->pageData['title'] = $title;
        }

        $this->pageData['title'] = htmlspecialchars($this->pageData['title']);
        return htmlspecialchars($title);
    }

    public function splitMessage($text, $min, $max)
    {
        $str = mb_substr($text, $min);
        $second = false;
        if (mb_strlen($str) > 0) {
            preg_match_all('/(.*)[\.\?\!]/U', $str, $second, PREG_OFFSET_CAPTURE);
            $second = $second['0']['0']['0'] ?? null;
        }

        $rText[0] = $second ? mb_substr($text, 0, $min) . $second : $text;

        $textSize = mb_strlen($rText[0]);

        $rText[1] = '';
        if ($textSize < mb_strlen($text)) {
            if ($max !== 0) {
                $rText[1] = mb_substr($text, $textSize, $max - $textSize);
                $rText[1] = Util::smartTrim($rText[1], $max - $textSize - 1);
            } else {
                $rText[1] = mb_substr($text, $textSize);
            }
        }

        return $rText;
    }

    public function setDescription($description = ''): void
    {
        if ($description == '') {
            $this->pageData['description'] = Util::smartTrim($this->pageData['content'], 170);
        } else {
            $this->pageData['description'] = $description;
        }
    }

    public function setKeywords($keywords = ''): void
    {
        $this->pageData['keywords'] = $keywords == '' ? Util::smartTrim($this->pageData['content'], 200) : $keywords;
    }

    public function getChildPosts($startMargin = 0, $margin = 50)
    {
        $db = Db::getInstance();
        $root = $this->pageData['ID'];

        $_sql = sprintf('SELECT * FROM %s WHERE is_active=1 AND main_parent_id=:main_parent_id %s', $this->_table, $this->where);
        $params = ['main_parent_id' => $root];
        $childPosts = $db->select($_sql, $params);

        $childPosts = $this->buildTree($childPosts, $root);
        $childPosts = $this->buildList($childPosts);

        foreach ($childPosts as $k => $post) {
            $childPosts[$k]['margin'] = $startMargin + $post['indent'] * $margin;
            $childPosts[$k]['content'] = nl2br($childPosts[$k]['content']);
            $childPosts[$k]['date_create'] = Util::dateReach($childPosts[$k]['date_create']) . ' ' . date('G:i', $post['date_create']);
        }

        return $childPosts;
    }

    public function getPost($ID)
    {
        $db = Db::getInstance();

        $_sql = sprintf('SELECT * FROM %s WHERE ID = :ID LIMIT 1', $this->_table);
        $params = ["ID" => $ID];

        return $db->select($_sql, $params);
    }

    public function setPrevStructure($pageStructure): void
    {
        $this->pageStructure = $pageStructure;
    }

    public function setPost($post): void
    {
        $this->post = $post;
    }

    public function addNewPost()
    {
        $db = Db::getInstance();
        $time = time();

        /*
         * Если тема или сообщение пишет подставной человек
         * то создается имитация активности форума с давних времен
         */
        if ((isset($this->post['is_poster']) && $this->post['is_poster'] == 'true') || ($this->post['email'] == 'zzz@zzz.zz')) {
            if ($this->post['main_parent_id'] !== '0') { // Если добавляется ответ
                $sql = "SELECT date_create FROM &table WHERE parent_id = :parent_id ORDER BY date_create";
                $params = ['parent_id' => $this->post['parent_id']];
                $fields = ['table' => $this->_table];
                $dates = $db->select($sql, $params, $fields);

                if (count($dates) > 0) { // Если на родительское сообщение уже были ответы
                    $date = end($dates);
                    $dateCreate = $date['date_create'];

                } else {
                    $sql = "SELECT date_create FROM &table WHERE ID = :parent_id";
                    $params = ['parent_id' => $this->post['parent_id']];
                    $fields = ['table' => $this->_table];
                    $date = $db->select($sql, $params, $fields);
                    $dateCreate = $date[0]['date_create'];
                }

                $threeWeek = 21 * 24 * 60 * 60; // 21 days; 24 hours; 60 mins; 60secs
                $time = mt_rand($dateCreate, $dateCreate + $threeWeek);

            } else { // Если создается тема
                $time = $par['date_create'] = mt_rand(mktime(0, 0, 0, 11, 1, 2012), mktime(0, 0, 0, 10, 31, 2013));
            }

            // Имитация завершена
        }

        $values['prev_structure'] = $this->prevStructure;
        $values['parent_id'] = $this->post['parent_id'];
        $values['main_parent_id'] = $this->post['main_parent_id'];
        $values['page_structure'] = $this->post['page_structure'];
        $values['author'] = $this->post['author'];
        $values['email'] = $this->post['email'];
        $values['content'] = $this->isModerator ? $this->post['content'] : strip_tags($this->post['content']);
        $values['referer'] = $_COOKIE['referer'] ?? 'empty';
        $values['date_create'] = $time;
        $values['is_active'] = 1;
        $values['is_mail'] = $this->post['is_mail'] ? 1 : 0;

        // Сообщения и темы созданные зарегестрированным пользователем по умолчанию отображаются.
        $values['is_moderated'] = intval($this->isModerator);


        $result = $db->insert($this->_table, $values);

        // При создании новой темы, устанавливаем отправку почты
        if ($values['main_parent_id'] == "0") {
            $values['is_mail'] = 1;
        }

        $this->subscribe([$this->post['email']], $this->post['main_parent_id'], $this->post['is_mail']);

        return $result; //ID нового ответа || false
    }

    public function updatePost()
    {
        $db = Db::getInstance();
        $values = [
            'author' => $this->post['author'],
            'email' => $this->post['email'],
            'content' => $this->post['content'],
        ];
        $params = ['ID' => $this->post['ID']];
        return $db
            ->update($this->_table)
            ->set($values)
            ->where('ID = :ID', $params)
            ->exec(); //true || false
    }

    public function deletePost()
    {
        $db = Db::getInstance();
        $result = $db->delete($this->_table)
                ->where('ID = :ID', ['ID' => $this->post['ID']])
                ->exec();

        if ($this->post['main_parent_id'] == 0) {
            // Это первое сообщение, удаляем всё подчистую
            $db->delete($this->_table)
                ->where('ID = :main_parent_id', ['main_parent_id' => $this->post['ID']])
                ->exec();

        } else {
            // Заменяем у всех потомков родительский id
            $db->update($this->_table)
                ->set(['parent_id' => $this->post['parent_id']])
                ->where('parent_id = :parent_id', ['parent_id' => $this->post['ID']])
                ->exec();
        }

        return $result; //true || false
    }

    /*
     * Установка отметки том что сообщение было опубликовано или было снято с публикации
     * */
    public function moderatedPost()
    {
        $db = Db::getInstance();

        return $db->update($this->_table)
            ->set(['is_moderated' => $this->post['isModerated']])
            ->where('ID = :ID', ['ID' => $this->post['ID']])
            ->exec(); //true || false
    }

    /*
     * Отключение рассылки
     * $emails - почтовые ящики тех, кому требуется отлючить рассылку
     * $mainPost - указывает на раздел, в от которого мы отписываем
     * $subscribe - если false, то отписываем, если true, то подписываем
     * */
    /**
     * @param string[] $emails
     */
    public function subscribe(array $emails, $mainPost, $subscribe)
    {
        if ($emails === []) {
            return false;
        }

        $where['param']['email'] = array_shift($emails);
        $where['sql'] = 'email = :email';

        foreach ($emails as $k => $email) {
            $where['param']['email' . $k] = $email;
            $where['sql'] .= ' OR email = :email' . $k;
        }

        // Если отписываемся, то ищем те поля, где подписка включена и наоборот
        if ($subscribe == false) {
            $setGetMail = 0;
            $whereGetMail = 1;
        } else {
            $setGetMail = 1;
            $whereGetMail = 0;
        }

        $db = Db::getInstance();
        $where['sql'] = 'is_mail = :whereGetMail AND main_parent_id = :mainPost AND ' . $where['sql'];
        $where['param']['whereGetMail'] = $whereGetMail;
        $where['param']['mainPost'] = $mainPost;

        return $db->update('i_miniforum_structure_post')
            ->set(['is_mail' => $setGetMail])
            ->where($where['sql'], $where['param'])
            ->exec();
    }

    /*
     * Рассылка сообщения всем подписчикам данного поста
     * $mainParentID - ID корневого сообщения*/
    // TODO функция требует оптимизации
    /**
     * @param array<string, mixed> $post
     */
    public function sendMessages(array $post): ?bool
    {
        $mail = new Mailer();
        $config = Config::getInstance();

        // Получаем массив почтовых ящиков менеджеров сайта пригодный для сравнения
        $emailManager = explode(',', $config->mailForm);
        foreach ($emailManager as $k => $email) {
            $emailManager[$k] = str_replace('<', '', $email);
            $emailManager[$k] = str_replace('>', '', $emailManager[$k]);
            $emailManager[$k] = trim($emailManager[$k]);
        }

        $link = $_SERVER['HTTP_REFERER'];

        if ($post['main_parent_id'] == "0") {
            $mail->setSubj($config->domain . ': новая тема на форуме');
            // TODO сделать адекватное получение url раздела
            $href = 'http://' . $_SERVER['SERVER_NAME'] . '/forum/' . $post['ID'] . $config->urlSuffix;

            $message = sprintf('<a href="%s">Новая тема на форуме</a> <br /><br />', $href)
                . 'Автор: ' . $post['author'] . "<br />"
                . 'Email: ' . $post['email'] . "<br />"
                . 'Сообщение: ' . "<br />" . $post['content'] . "<br /><br />"
                . sprintf("На странице: <a href='%s'>%s</a>", $link, $link);
            $mail->setBody('', $message);
            $emailSend = [];
            foreach ($emailManager as $email) {
                // Не отправляем сообщение менеджеру сайта, в случае если он создал новую тему
                if ($email == $post['email']) {
                    continue;
                }

                $emailSend[] = '<' . $email . '>';
            }

            $emailSend = implode(", ", $emailSend);
            $mail->sent($config->robotEmail, $emailSend);
            return true;
        }

        $db = Db::getInstance();
        $_sql = "SELECT email, ID, date_create, main_parent_id FROM &table WHERE (is_mail = 1 AND main_parent_id = :main_parent_id) OR ID = :main_parent_id GROUP BY email";
        $params = ['main_parent_id' => $post['main_parent_id']];
        $fields = ['table' => 'i_miniforum_structure_post'];
        $postsDB = $db->select($_sql, $params, $fields);
        // Если нет почтовых ящиков, возвращаем false
        if (!$postsDB) {
            return false;
        }

        // Задаём тему для всех отправляемых сообщений
        $mail->setSubj($config->domain . ': новый ответ на сообщение');
        $href = 'http://' . $_SERVER['SERVER_NAME'] . '/forum/' . $post['main_parent_id'] . $config->urlSuffix;
        if (isset($post['ID'])) {
            $href = $href . '#post-line-' . $post['ID'];
        };

        // Сообщение для менеджера сайта
        $message = sprintf('<a href="%s">На форуме сайта %s появился новый ответ</a> <br /><br />', $href, $config->domain)
            . 'Автор: ' . $post['author'] . "<br />"
            . 'Email: ' . $post['email'] . "<br />"
            . 'Сообщение: ' . "<br />" . $post['content'] . "<br /><br />"
            . sprintf("На странице: <a href='%s'>%s</a>", $link, $link);
        $mail->setBody('', $message);
        $mail->sent($config->robotEmail, $config->mailForm);

        $message = "Здравствуйте! <br />
                    На форуме сайта {$config->domain} появился новый ответ на Ваше сообщение, <br />
                    Автор ответа : {$post['author']} <br />";
        $message .= sprintf('Прочитать ответ: <a href="%s">"%s"</a> <br /><br />', $href, $href);

        foreach ($postsDB as $postDB) {
            // Не отправляем повторно письмо менеджерам сайта
            if (in_array($postDB['email'], $emailManager)) {
                continue;
            }

            // Создаём хеш на основе данных поста
            $hash = $postDB['email'] . $postDB['main_parent_id'] . $postDB['ID'] . $postDB['date_create'];
            $hash = crypt($hash, (string) $postDB['ID']);
            // Создаём ссылку для отписки
            $hrefUnsubscribe = $href . '?email=' . urlencode($postDB['email']) . '&post=' . urlencode($postDB['main_parent_id']) . '&id=' . urlencode($postDB['ID']) . '&hash=' . urlencode($hash);
            $unsubscribe = sprintf('Чтобы больше не получать уведомления об ответах на Ваше сообщение нажмите сюда: <a href="%s">%s</a></br>', $hrefUnsubscribe, $hrefUnsubscribe);
            // Не даём отписаться создателю темы
            if ($postDB['ID'] === $post['main_parent_id']) {
                $unsubscribe = '';
            }

            $mail->setBody('', $message . $unsubscribe);
            $mail->sent($config->robotEmail, $postDB['email']);
        }

        return null;
    }


    public function unsubjectLink($email, $id, $mainParentID, $hash): bool
    {
        $db = Db::getInstance();

        $email = $db->escape_string((urldecode($email)));
        $id = $db->escape_string((urldecode($id)));
        $mainParentID = $db->escape_string((urldecode($mainParentID)));
        $hash = $db->escape_string((urldecode($hash)));

        $_sql = "SELECT email, ID, date_create, main_parent_id FROM i_miniforum_structure_post WHERE ID = :ID";
        $params = ['ID' => $id];
        $fields = ['table' => 'i_miniforum_structure_post'];
        $post = $db->select($_sql, $params, $fields);
        $trueHash = $post[0]['email'] . $post[0]['main_parent_id'] . $post[0]['ID'] . $post[0]['date_create'];
        $trueHash = crypt($trueHash, (string) $post[0]['ID']);
        if ($trueHash === $hash) {
            $this->subscribe([$email], $mainParentID, false);
            return true;
        }

        return false;
    }

    /**
     * Функция для генерации карты сайта в формате html
     * @return array
     */
    public function getStructureElements()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();

        $_sql = sprintf('SELECT * FROM %s WHERE is_active=1 AND parent_id=0 AND is_moderated=1', $this->_table);
        $list = $db->select($_sql);

        foreach ($list as $k => $v) {
            $list[$k]['name'] = $this->splitMessage($v['content'], 10, 80);
            $list[$k]['name'] = $list[$k]['name'][0] . $list[$k]['name'][1];
            $list[$k]['link'] = $this->parentUrl . '/' . $v['ID'] . $config->urlSuffix;
        }

        return $list;
    }

    protected function getWhere($where): string
    {
        return 'WHERE ' . $where . $this->where . ' AND is_active=1 AND parent_id=0';
    }

    /**
     */
    protected function buildChildrenPosts($margin, $parent)
    {
        $end = end($parent);

        $children = $end['elements'] ?? null;

        if (!isset($children[0]['ID'])) {
            return $parent;
        }

        foreach ($children as &$v) {
            $v['margin'] = $v['indent'] * $margin;
        }

        unset($parent[0]['elements']);
        $parent = array_merge($parent, $children);

        return $this->buildChildrenPosts($margin, $parent);
    }

    /**
     * @param array<string, mixed> $post
     */
    protected function parsePost(array $post): array
    {
        $config = Config::getInstance();

        $post['link'] = '/forum' . '/' . $post['ID'] . $config->urlSuffix;
        $post['date_create'] = Util::dateReach($post['date_create']) . ' ' . date('G:i', $post['date_create']);

        //Резделяем текст в соответствии с условиями
        $text = $this->splitMessage($post['content'], 30, 200);

        $post['firstText'] = $text[0];
        $post['secondText'] = $text[1];

        if ((mb_strlen($post['firstText'] . $post['secondText']) < mb_strlen($post['content']))
                && ($post['secondText'] !== '')) {
            $post['secondText'] .= '...';
        }

        $post['firstText'] = str_replace('\r\n', ' ', $post['firstText']);
        $post['secondText'] = str_replace('\r\n', ' ', $post['secondText']);

        return $post;
    }

    /**
     * Получение количества ответов на пост
     *
     * @param $post
     * @return mixed
     * @param array<string, mixed> $postID
     */
    protected function getAnswerCount(array $postID)
    {
        $db = Db::getInstance();
        $_sql = sprintf('SELECT COUNT(*) AS count FROM %s WHERE main_parent_id=:main_parent_id', $this->_table);
        $params = ['main_parent_id' => $postID['ID']];
        $answerCount = $db->select($_sql, $params);
        return $answerCount[0]['count'] ?? false;
    }

    /**
     * @return mixed[]
     */
    protected function buildTree(&$list, $root): array
    {
        $tree = [];
        foreach ($list as $v) {
            if ($v['parent_id'] == $root) {
                $v['elements'] = $this->buildTree($list, $v['ID']);
                $tree[] = $v;
            }
        }

        return $tree;
    }

    /**
     * @return mixed[]
     */
    protected function buildList($tree, $indent = 0): array
    {
        $list = [];
        foreach ($tree as $v) {
            $v['indent'] = $indent;
            $newList = $this->buildList($v['elements'], $indent + 1);
            unset($v['elements']);
            $list[] = $v;
            $list = array_merge($list, $newList);
        }

        return $list;
    }
}
