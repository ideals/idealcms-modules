<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace CatalogPlus\Structure\Category\Admin;

use Ideal\Core\Db;

class ModelAbstract extends \Ideal\Structure\Part\Admin\Model
{
    /**
     * @var array category Массив категорий
     */
    protected $category = array();
    /**
     * Выполняет загрузку категорий на сайте.
     * @param string $key Указывает по какому ключу стоить массив, для 1с это будет id_1c для остальных случаев name
     */
    public function loadCategory($key = '1c_id')
    {
        $db = Db::getInstance();
        $table = $this->_table;
        $_sql = "SELECT * FROM {$table} ORDER BY cid";
        $result = $db->select($_sql);
        foreach ($result as $k => $v) {
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
