<?php
namespace Shop\Structure\Service\Load1cV2;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 09.07.2015
 * Time: 19:42
 */

class AbstractXml
{
    /** @var Xml */
    protected $xml;

    /** @var string namespace, используемый в запросах */
    protected $ns;

    /** @var array данные из конфигурационного файла класса с подготовленными xpath запросами */
    protected $configs;

    /** @var array многомерный массив заполняемый в методе parse */
    protected $data;

    /** @var bool значение СодержитТолькоИзменения из xml */
    protected $updateInfo;

    /** @var array данные о namespace'ах из xml */
    protected $namespaces;

    /** @var string подготовленный xpath запрос для отделение необходимой части xml */
    protected $part;

    /**
     * Получение необходимой части выгрузки из всего xml, объявление namespace по умолчанию,
     * подключение конфигураций, получение информации об updateInfo
     *
     * @param Xml $xml
     */
    public function __construct(Xml $xml)
    {
        $this->xml = $xml->getPart($this);
        if (!empty($this->xml)) {
            $this->xml = $this->xml[0];

            $this->namespaces = $this->xml->getDocNamespaces();

            $this->registerNamespace($this->xml);

            $path = explode('\\', get_class($this));
            $path = array_slice($path, -2, 1);
            $path = 'Shop/Structure/Service/Load1cV2/' . $path[0];
            $this->configs = include $path . '/config.php';
            $firsPart = explode('/', $this->part);
            $part = array_shift($firsPart);
            $updateInfo = $this->xml->xpath('//' . $this->ns . $part . '/@СодержитТолькоИзменения');

            // по умолчанию считаем что выгружаются все данные, а не только обновления
            $this->updateInfo = false;
            if (!empty($updateInfo)) {
                $this->updateInfo = (string) $updateInfo[0] == 'false' ? false : true;
            }
        } else {
            $this->updateInfo = true;
        }
    }

    public function getXml()
    {
        return $this->xml;
    }

    /**
     * Getter updateInfo
     *
     * @return bool
     */
    public function updateInfo()
    {
        return $this->updateInfo;
    }

    /**
     * Преобразование xml к многомерному массиву значений
     */
    public function parse()
    {
        foreach ($this->xml as $item) {
            $id = $this->getXmlId($item);
            $this->data[$id] = array();

            $this->registerNamespace($item);

            $this->updateFromConfig($item, $id);
        }
    }

    /**
     * Проверяет соответствие переданных данных ожидаемой структуре
     */
    public function validate()
    {
        return !empty($this->xml);
    }

    /**
     * Получает идентификатор из данных Xml
     *
     * @param \SimpleXMLElement $item данные в xml формате
     * @return string Идентификатор определённый в конфиге
     */
    protected function getXmlId($item)
    {
        return (string) $item->{$this->configs['key']};
    }

    /**
     * Получение значений для элементов $this->data с использование xpath запросов к node
     * Пути для запросов берутся из конфигурационного файла.
     *
     * @param \SimpleXmlElement $item node Часть выгрузки, на которую будет выполняться запрос
     * @param string $id Ключ массива, указанный в конфигурационном файле
     */
    protected function updateFromConfig($item, $id)
    {
        // Проходимся по тем полям, которые надо добавить в БД
        foreach ($this->configs['fields'] as $key => $value) {
            $path = is_array($value) ? $value['path'] : $value;
            $path = implode('/' . $this->ns, explode('/', $path));
            $path = str_replace('`', $this->ns, $path);

            $needle = $item->xpath($this->ns . $path);

            // По умолчанию присваиваем заполняемому полю пустую строку
            $this->data[$id][$key] = is_array($value) ? array() : '';

            // Если требуется заполнить обычное скалярное поле
            if (!is_array($value) && isset($needle[0])) {
                $this->data[$id][$key] = (string) $needle[0];
            }

            // Если требуется заполнить одномерный массив
            if (is_array($value) && !isset($value['field'])) {
                foreach ($needle as $node) {
                    $this->data[$id][$key][] = (string) $node;
                }
            }

            // Если требуется заполнить двумерный массив
            if (is_array($value) && isset($value['field'])) {
                foreach ($needle as $node) {
                    $this->registerNamespace($node);
                    $tmp = array();
                    foreach ($value['field'] as $name => $conf) {
                        $res = $node->xpath($this->ns . $conf);
                        // todo остался нерешённым ворос, что будет, если в $res будет несколько элементов?
                        $tmp[$name] = (string) $res[0];
                    }
                    $this->data[$id][$key][] = $tmp;
                }
            }
        }
    }

    /**
     * Установка значений default namespace для дальнейших запросов к node
     *
     * @param \SimpleXmlElement $item
     */
    protected function registerNamespace($item)
    {
        if (isset($this->namespaces[''])) {
            $item->registerXPathNamespace('default', $this->namespaces['']);
            if (!isset($this->ns)) {
                $this->ns = 'default:';
            }
        }
    }
}
