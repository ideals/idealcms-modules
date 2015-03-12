<?php
namespace CatalogPlus\Structure\Brand\Site;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Request;
use Ideal\Structure\Part;

class ModelAbstract extends Part\Site\Model
{

    public function getWhere($where)
    {
        if ($where != '') {
            $where .= " AND ";
        }
        $where = 'WHERE ' . $where . ' is_active=1';

        return $where;
    }

    /**
     * Получить общее количество элементов в списке
     * @return array Полученный список элементов
     */
    public function getListCount()
    {
        $db = Db::getInstance();
        $where = $this->getWhere("e.prev_structure='{$this->prevStructure}'");

        // Считываем все элементы первого уровня
        $_sql = "SELECT COUNT(e.ID) FROM {$this->_table} AS e {$where}";
        $list = $db->select($_sql);

        return $list[0]['COUNT(e.ID)'];
    }

    public function detectPageByUrl($path, $url)
    {
        $db = Db::getInstance();

        $url = $db->real_escape_string(end($url));
        $sql = "SELECT * FROM {$this->_table} WHERE is_active=1 AND url='{$url}'  AND date_create < " . time();

        $news = $db->select($sql); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($news[0]['ID'])) {
            return '404';
        }

        $news[0]['structure'] = 'CatalogPlus_Brand';
        $news[0]['url'] = $url;

        $this->path = array_merge($path, $news);

        $request = new Request();
        $request->action = 'detail';
        return $this;
    }


    public function getText()
    {
        $config = Config::getInstance();
        $db = Db::getInstance();

        if (isset($this->pageData['content'])) {
            $text = $this->pageData['content'];
        } else {
            // TODO проработать ситуацию, когда текст в шаблоне (сейчас нет определения модуля)
            $table = $config->db['prefix'] . 'Template_' . $this->pageData['template'];
            $structurePath = $this->pageData['structure_path'] . '-' . $this->pageData['ID'];
            $text = $db->select($table, $structurePath, '', 'structure_path');
            $text = $text[0]['content'];
        }

        $header = '';
        if (preg_match('/<h1>(.*)<\/h1>/isU', $text, $header)) {
            $text = preg_replace('/<h1>(.*)<\/h1>/isU', '', $text, 1);
            $this->header = $header[1];
        }
        return $text;
    }


    public function setObjectNew()
    {

    }


    public function getStructureElements()
    {
        $list = $this->getList(0, 9999);
        return $list;
    }
}
