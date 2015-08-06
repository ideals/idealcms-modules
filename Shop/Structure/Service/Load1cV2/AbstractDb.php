<?php
namespace Shop\Structure\Service\Load1cV2;

use Ideal\Core\Config;
use Ideal\Core\Db;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 17.07.2015
 * Time: 13:39
 */

class AbstractDb
{
    /** @var string основная таблица */
    protected $table;

    /** @var string префикс таблиц */
    protected $prefix;

    /** @var array массив конфигурация для таблицы */
    protected $configs;

    /** @var bool выгрузка содержит только изменения */
    protected $onlyUpdate;

    /** @var int количество вставляемых строк за 1 insert запрос */
    protected $multipleInsert = 5;

    /**
     *  Установка полей класса - полного имени таблиц с префиксами и получения prev_structure
     */
    public function __construct()
    {
        $config = Config::getInstance();
        $this->prefix = $config->db['prefix'];
        $path = explode('\\', get_class($this));
        $path = array_slice($path, -2, 1);
        $this->configs = include $path[0] . '/config.php';
    }

    protected function truncate()
    {
        $db = Db::getInstance();

        $sql = "TRUNCATE {$this->table}";

        $db->query($sql);
    }

    /**
     * Обновление данных в БД
     *
     * @param array $element массив данных об обновляемой строке БД
     */
    protected function update($element)
    {
        $db = Db::getInstance();

        $element['date_mod'] = time();

        $db->update($this->table)->set($element)->where('ID=:ID', $element)->exec();
    }

    /**
     * Добавление нового товара в БД
     *
     * @param array $element данные для добавления в БД
     */
    protected function add($element)
    {
        $db = Db::getInstance();

        $element['date_create'] = time();
        $element['date_mod'] = time();

        $db->insert($this->table, $element);
    }

    /**
     * Сохранение полученных из XML изменений
     *
     * @param array $elements массив данных для записи в базу данных
     */
    public function save($elements)
    {
        foreach ($elements as $element) {
            if (isset($element['ID'])) {
                $this->update($element);
            } else {
                if (!$this->onlyUpdate) {
                    $element['date_create'] = time();
                    $element['date_mod'] = time();
                    $add[] = $element;
                } else {
                    $this->add($element);
                }
            }
        }

        if (isset($add)) {
            $db = Db::getInstance();
            while (count($add) >= $this->multipleInsert) {
                $part = array_splice($add, 0, $this->multipleInsert);
                $db->insertMultiple($this->table, $part);
            }
        }
    }

    public function onlyUpdate($onlyUpdate)
    {
        if (!$onlyUpdate) {
            $this->truncate();
        }
        $this->onlyUpdate = $onlyUpdate;
    }
}
