<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace CatalogPlus\Structure\Category\Admin;

use Ideal\Structure\Part\Admin\Model;
use Ideal\Core\Db;

class ModelAbstract extends Model
{
    /**
     * @var array category Массив категорий
     */
    protected $category = [];

    /**
     * Выполняет загрузку категорий на сайте.
     * @param string $key Указывает по какому ключу стоить массив, для 1с это будет id_1c для остальных случаев name
     */
    public function loadCategory($key = '1c_id'): void
    {
        $db = Db::getInstance();
        $table = $this->_table;
        $_sql = sprintf('SELECT * FROM %s ORDER BY cid', $table);
        $result = $db->select($_sql);
        foreach ($result as $v) {
            $this->category[$v['id_1c']] = $v;
        }
    }

    /**
     * Получение ID категории по её наименованию
     * @param $nameCategory Наименование категории
     * @return mixed ID категории или false если категория не найдена
     */
    public function getIdCategory($nameCategory)
    {
        if (!isset($this->category[$nameCategory])) {
            return false;
        }

        return $this->category[$nameCategory];
    }
}
