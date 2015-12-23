<?php
namespace Cabinet\Structure\Part\Site;

use Ideal\Field;

class ModelAbstract extends \Ideal\Structure\Part\Site\Model
{

    /**
     * Генерация абсолютного пути до страницы логина/регистрации/подтверждения
     *
     * @return string Абсолютный путь до страницы логина/регистрации/подтверждения
     */
    public function getFullUrl()
    {
        $pageData = $this->getPageData();
        $url = new Field\Url\Model();
        $link = $url->getUrl($pageData);
        return $link;
    }
}
