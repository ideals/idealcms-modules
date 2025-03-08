<?php
namespace Shop\Structure\Service\Load1CV3\Db;

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
        $path = 'Shop/Structure/Service/Load1CV3/Xml/' . $path[0];
        $this->configs = include $path . '/config.php';
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getTablePostfix()
    {
        return $this->tablePostfix;
    }

    /**
     * Удаление временной таблицы
     */
    public function dropTestTable()
    {
        $db = Db::getInstance();
        $testTable = $this->table . $this->tablePostfix;

        if ($this->tableExist()) {
            $sql = "DROP TABLE {$testTable}";
            $db->query($sql);
        }
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
        // Проводим обработку элементов, специфичную для каждого вида данных
        $elements = $this->prepareForSave($elements);

        foreach ($elements as $element) {
            if (isset($element['ID'])) {
                $this->update($element);
            } else {
                $this->insert($element);
            }
        }
    }

    /**
     * Подготовка временной таблицы для занесения данных
     */
    public function prepareTable()
    {
        $this->dropTestTable();
        $this->createEmptyTestTable();
        $this->copyOrigTable();
    }

    public function renameTable()
    {
        $this->updateOrigTable();
        $this->dropTestTable();
    }

    public function deactivateTable(): void
    {
    }

    /**
     * Определяем, создана ли уже тестовая таблица
     *
     * @return bool
     */
    public function tableExist()
    {
        $db = Db::getInstance();

        $testTable = $this->table . $this->tablePostfix;
        $sql = "show tables like '{$testTable}'";
        $result = $db->query($sql);
        $res = $result->fetch_all(MYSQLI_ASSOC);

        return count($res) > 0;
    }

    /**
     * Свап временной и оригинальной таблицы
     */
    public function updateOrigTable()
    {
        $db = Db::getInstance();

        $testTable = $this->table . $this->tablePostfix;

        // Проверяем наличие тестовых таблиц, потому что их может не быть если происходит только лишь обмен заказами
        $result = $db->query('SHOW TABLES LIKE \'' . $testTable . '\'');

        $res = $result->fetch_all(MYSQLI_ASSOC);
        if (count($res) > 0) {
            $sql = "RENAME TABLE {$this->table} TO {$this->table}_tmp,
             {$testTable} TO {$this->table},
             {$this->table}_tmp TO {$testTable}";
            $db->query($sql);
        }
    }

    /**
     * Обновление данных в БД
     *
     * @param array $element массив данных об обновляемой строке БД
     * @param ?array $oldElement
     */
    public function update($element, $oldElement = null)
    {
        $db = Db::getInstance();

        $element = $this->prepareForUpdate($element, $oldElement);

        if (count($element) < 2) {
            // Если нет данных или указан только ID, то не обновляем
            return;
        }

        $element['date_mod'] = time();

        $db->update($this->table . $this->tablePostfix)->set($element)->where('ID=:ID', $element)->exec();
    }

    /**
     * Обновление данных в БД
     *
     * @param array $element массив данных об обновляемой строке БД
     * @return int Идентификатор добавленой записи
     */
    public function insert($element)
    {
        $db = Db::getInstance();
        $element = $this->getForAdd($element);
        $id = $db->insert($this->table . $this->tablePostfix, $element);
        $this->afterInsert($id, $element);
        return $id;
    }

    /**
     * Вызывается перед записью данных в БД. Есть возможность скорректировать, или отменить запись
     */
    public function onBeforeSetDbElement(?array $old, array &$new): bool
    {
        return true;
    }

    /**
     * В рамках экземпляра этого класса является методом-заглушкой.
     * Метод запускается после каждой фиксации изменения в базе данных.
     * Паттерн 'Observer' не используется потому, что всё действие происходит в иерархии наследуемых классов без
     * посторонних включений.
     * @param array $dbData Данные из БД
     * @param array $xmlData Данные из Xml
     */
    public function onAfterSetDbElement($dbData, $xmlData)
    {
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
     * Подготовка параметров для добавления элемента в БД
     *
     * @param array $element Добавляемый элемент
     * @return array Модифицированный элемент
     */
    protected function getForAdd($element)
    {
        $now = time();
        $element['date_create'] = empty($element['date_create']) ? $now : $element['date_create'];
        $element['date_mod'] = empty($element['date_mod']) ? $now : $element['date_mod'];

        ksort($element);

        return $element;
    }

    /**
     * Проводим подготовку элементов перед их сохранением в БД
     * @param array $elements
     * @return array
     */
    protected function prepareForSave($elements)
    {
        return $elements;
    }

    /**
     * Заглушка, для внесения изменений после вставки элемента
     *
     * @param int $id
     * @param array $element
     */
    protected function afterInsert($id, $element)
    {
    }

    /**
     * Подготовка элемента к обновлению в БД
     *
     * @param array $element Новый набор данных элемента
     * @param array $oldElement Старые данные элемента
     * @return array
     */
    protected function prepareForUpdate($element, $oldElement)
    {
        return $element;
    }
}
