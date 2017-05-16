<?php
namespace Cabinet\Structure\Part\Site;

use Ideal\Field;
use Ideal\Core\Util;
use Ideal\Core\Db;
use Ideal\Core\Request;

class ModelAbstract extends \Ideal\Structure\Part\Site\Model
{

    public function detectPageByUrl($path, $url)
    {
        $db = Db::getInstance();

        $_sql = "SELECT * FROM {$this->_table} WHERE BINARY url=:url AND date_create < :time";
        $par = array('url' => $url[0], 'time' => time());

        $cabinetParts = $db->select($_sql, $par); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($cabinetParts[0]['ID'])) {
            $this->path = $path;
            $this->is404 = true;
            return $this;
        }

        if (count($cabinetParts) > 1) {
            $c = count($cabinetParts);
            Util::addError("В базе несколько ({$c}) страниц личного кабинета с одинаковым url: " . implode('/', $url));
            $cabinetParts = array($cabinetParts[0]); // оставляем для отображения первую новость
        }

        $cabinetParts[0]['structure'] = 'Cabinet_Part';
        $cabinetParts[0]['url'] = $url[0];

        $this->path = array_merge($path, $cabinetParts);

        $request = new Request();
        if (empty($request->action)) {
            $request->action = 'detail';
        }

        return $this;
    }

    /**
     * Генерация абсолютного пути до страницы логина/регистрации/подтверждения
     *
     * @return string Абсолютный путь до страницы логина/регистрации/подтверждения
     */
    public function getFullUrl()
    {
        $pageData = $this->getPageData();
        $url = new Field\Url\Model();
        if (count($this->path) > 2) {
            $url->setParentUrl($this->path);
        }
        $link = $url->getUrl($pageData);
        return $link;
    }
}
