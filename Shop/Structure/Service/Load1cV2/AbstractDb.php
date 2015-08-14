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

    /** @var string промежуточная таблица */
    protected $tablePostfix = '_test';

    /** @var string префикс таблиц */
    protected $prefix;

    /** @var array массив конфигурация для таблицы */
    protected $configs;

    /** @var bool выгрузка содержит только изменения */
    protected $onlyUpdate;

    /** @var int количество вставляемых строк за 1 insert запрос */
    protected $multipleInsert = 5;

    /**
     *  Установка полей класса - префикса таблиц, конфигураций из config.php
     */
    public function __construct()
    {
        $config = Config::getInstance();
        $this->prefix = $config->db['prefix'];
        $path = explode('\\', get_class($this));
        $path = array_slice($path, -2, 1);
        $this->configs = include $path[0] . '/config.php';
    }

    /**
     * Создание временной таблицы для сохранения данных со схемой оригинальной таблицы
     */
    protected function createEmptyTestTable()
    {
        $db = Db::getInstance();
        $testTable = $this->table . $this->tablePostfix;

        $sql = "CREATE TABLE {$testTable} LIKE {$this->table}";
        $db->query($sql);
    }

    /**
     * Удаление временной таблицы
     */
    protected function dropTestTable()
    {
        $db = Db::getInstance();

        $testTable = $this->table . $this->tablePostfix;
        $sql = "show tables like '{$testTable}'";
        $result = $db->query($sql);
        $res = $result->fetch_all(MYSQLI_ASSOC);
        if (count($res) > 0) {
            $sql = "DROP TABLE {$testTable}";
            $db->query($sql);
        }
    }

    /**
     * Копирование данных из оригинальной таблицы во временную.
     * Необходимо при обновлении данных ($onlyUpdates = true)
     */
    protected function copyOrigTable()
    {
        $db = Db::getInstance();

        $testTable = $this->table . $this->tablePostfix;
        $sql = "INSERT INTO {$testTable} SELECT * FROM {$this->table}";
        $db->query($sql);
    }

    /**
     * Свап временной и оригинальной таблицы
     */
    public function updateOrigTable()
    {
        $db = Db::getInstance();

        $testTable = $this->table . $this->tablePostfix;
        $sql = "RENAME TABLE {$this->table} TO {$this->table}_tmp,
             {$testTable} TO {$this->table},
             {$this->table}_tmp TO {$testTable}";
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

        $db->update($this->table . $this->tablePostfix)->set($element)->where('ID=:ID', $element)->exec();
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

        $db->insert($this->table . $this->tablePostfix, $element);
    }

    /**
     * Сохранение полученных из XML изменений. Возможна как построчная вставка, так и вставка массивом значений
     * INSERT INTO `table` (col1, col2, col3) VALUES (1,2,3), (5,6,7) ...
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
                    ksort($element);
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
                $db->insertMultiple($this->table . $this->tablePostfix, $part);
            }
        }
    }

    /**
     * Подготовка временной таблицы для занесения данных
     *
     * @param bool $onlyUpdate значение СодержитТолькоИзменения из xml выгрузки
     */
    public function prepareTable($onlyUpdate)
    {
        $this->onlyUpdate = $onlyUpdate;
        $this->dropTestTable();
        $this->createEmptyTestTable();
        if ($onlyUpdate) {
            $this->copyOrigTable();
        }
    }
}
