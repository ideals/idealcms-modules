<?php
namespace CatalogPlus\Structure\Offer\Site;

use CatalogPlus;
use Ideal\Core\Db;
use CatalogPlus\Structure\Good\Site\ModelAbstract as GoodAbstract;

class ModelAbstract extends GoodAbstract
{

    public function detectPageByUrl($path, $url)
    {
        // Определяем, нет ли в URL категории
        $this->categoryModel = new CatalogPlus\Structure\Category\Site\Model($this->prevStructure);
        $model = $this->categoryModel->detectPageByUrl($path, $url);
        if (!$model->is404) {
            // Прошло успешно определение страницы категории, значит товар определять не надо
            return $model;
        }

        if (count($url) > 1) {
            // У товара не может быть URL с несколькими уровнями вложенности
            $this->is404 = true;
            $this->path = $path;
            return $this;
        }

        $url = array_shift($url);

        // Ищем товар по URL в базе
        $db = Db::getInstance();
        $_sql = "SELECT * FROM {$this->_table} WHERE url='{$url}' LIMIT 1";
        $list = $db->select($_sql);

        // Товар не нашли, возвращаем 404
        if (!isset($list[0]['ID'])) {
            $this->is404 = true;
            return $this;
        }

        // Товар найден, проводим необходимую инициализацию свойств

        $this->path = array_merge($path, $list);
        $this->pageData = end($list);

        return $this;
    }

    /**
     * Установка свойств объекта по данным из массива $model
     *
     * Вызывается при копировании данных из одной модели в другую
     * @param Model $model Массив переменных объекта
     * @return object Либо ссылка на самого себя, либо новый объект модели
     */
    public function setVars($model)
    {
        $model = parent::setVars($model, true);
        $pageData = $model->getPageData();
        $pageData['offers'] = $model->getOffers($model->prevStructure);
        $model->setPageData($pageData);
        return $model;
    }

    public function getOffers($prevStructure)
    {
        // Ищем все офферы товара по преструктуре
        $db = Db::getInstance();
        $_sql = "SELECT * FROM {$this->_table} WHERE prev_structure='{$prevStructure}'";
        $list = $db->select($_sql);
        return $list;
    }
}
