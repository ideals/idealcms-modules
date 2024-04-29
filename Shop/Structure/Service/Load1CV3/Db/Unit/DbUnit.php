<?php
namespace Shop\Structure\Service\Load1CV3\Db\Unit;

use Shop\Structure\Service\Load1CV3\Db\AbstractDb;
use Ideal\Core\Db;

class DbUnit extends AbstractDb
{
    protected $parse = [];

    /**
     *  Установка полей класса - полного имени таблиц с префиксом
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = $this->prefix . 'catalogplus_structure_unit';
    }

    /**
     * Парсинг товаров из БД
     *
     * @return array ключ - id_1c, значение - все необходимые поля (в SQL)
     */
    public function parse()
    {
        if (count($this->parse) !== 0) {
            return $this->parse;
        }

        $db = Db::getInstance();

        $sql = 'SELECT * FROM ' . $this->table . $this->tablePostfix;
        $result = $db->select($sql);

        foreach ($result as $item) {
            $this->parse[$item['id_1c']] = $item;
        }

        return $this->parse;
    }
}
