<?php

namespace Cabinet\Structure\Part\Site;

use Ideal\Structure\Part\Site\Model;
use Ideal\Field;
use Ideal\Core\Util;
use Ideal\Core\Db;
use Ideal\Core\Request;

class ModelAbstract extends Model
{
    public function detectPageByUrl($path, $url): \Ideal\Core\Site\Model
    {
        $db = Db::getInstance();

        $_sql = sprintf('SELECT * FROM %s WHERE BINARY url=:url AND date_create < :time', $this->_table);
        $par = ['url' => $url[0], 'time' => time()];

        $cabinetParts = $db->select($_sql, $par); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($cabinetParts[0]['ID'])) {
            $this->path = $path;
            $this->is404 = true;
            return $this;
        }

        if (count($cabinetParts) > 1) {
            $c = count($cabinetParts);
            Util::addError(sprintf('В базе несколько (%d) страниц личного кабинета с одинаковым url: ', $c) . implode('/', $url));
            $cabinetParts = [$cabinetParts[0]]; // оставляем для отображения первую новость
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

        return $url->getUrl($pageData);
    }
}
