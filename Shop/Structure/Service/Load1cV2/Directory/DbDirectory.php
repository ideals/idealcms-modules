<?php
namespace Shop\Structure\Service\Load1cV2\Directory;

use Shop\Structure\Service\Load1cV2\AbstractDb;
use Ideal\Field\Url;
use Shop\Structure\Service\Load1cV2\Category;
use Ideal\Core\Db;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:31
 */

class DbDirectory extends AbstractDb
{
    /**
     *  Установка полей класса - полного имени таблиц с префиксами и получения prev_structure
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = $this->prefix . 'catalogplus_structure_directory';
    }

    /**
     * Парсинг товаров из БД
     *
     * @return array ключ - id_1c, значение - все необходимые поля (в SQL)
     */
    public function parse()
    {
        $db = Db::getInstance();

        // Считываем товары из нашей БД
        $sql = "SELECT * FROM " . $this->table . $this->tablePostfix;

        $tmp = $db->select($sql);

        $result = array();
        foreach ($tmp as $value) {
            $result[$value['dir_value_id']] = $value;
        }

        return $result;
    }

    /**
     * Получение ИД справочников для товара по id_1c и значению справочника
     *
     * @param $params array
     * @return string json строка с значениями справочников
     */
    public function getDirectory($params)
    {
        $db = Db::getInstance();

        $where = array();
        foreach ($params as $value) {
            $where[] = " (`dir_id_1c` = '{$value['dir_id_1c']}' AND `dir_value_id` = '{$value['dir_value_id']}') ";
        }

        $where = implode('OR', $where);

        // Считываем товары из нашей БД
        $sql = "SELECT ID FROM " . $this->table . $this->tablePostfix . " WHERE {$where}";
        $result = $db->select($sql);

        return json_encode($result);
    }
}
