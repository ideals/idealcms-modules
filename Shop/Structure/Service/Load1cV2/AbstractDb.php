<?php
namespace Shop\Structure\Service\Load1cV2;

use Ideal\Core\Config;
use Ideal\Core\Db;

/**
 * Абстрактный класс для работы с таблицами в базе данных
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
        $path = 'Shop/Structure/Service/Load1cV2/' . $path[0];
        $this->configs = include $path . '/config.php';
    }

    /**
     * Удаление временной таблицы
     */
    public function dropTestTable()
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
     * Копирование данных из оригинальной таблицы во временную
     */
    protected function copyOrigTable()
    {
        $db = Db::getInstance();

        $testTable = $this->table . $this->tablePostfix;
        $sql = "INSERT INTO {$testTable} SELECT * FROM {$this->table}";
        $db->query($sql);
    }

    /**
     * Переводим все данные во временных таблицах в is_active = 0.
     * Используется только при полной выгрузке.
     */
    protected function deactivateDataInTable()
    {
        $db = Db::getInstance();

        $testTable = $this->table . $this->tablePostfix;
        $sql = "UPDATE {$testTable} SET is_active = 0";
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
     * Подготовка параметров для добавления элемента в БД
     *
     * @param array $element Добавляемый элемент
     * @return array Модифицированный элемент
     */
    protected function getForAdd($element)
    {
        $now = time();
        $element['date_create'] = $now;
        $element['date_mod'] = $now;

        ksort($element);

        return $element;
    }

    /**
     * Сохранение полученных из XML изменений (обновление существующих, добавление новых)
     *
     * Возможна как построчная вставка, так и вставка массивом значений
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
                $add[] = $this->getForAdd($element);
            }
        }

        // Если есть что добавить, то добавляем мульти-запросами
        if (isset($add)) {
            $db = Db::getInstance();
            while (count($add) > 0) {
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
        $this->copyOrigTable();
        if (!$onlyUpdate) {
            $this->deactivateDataInTable();
        }
    }
}
