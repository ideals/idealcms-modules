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
        $this->xml = $this->xml[0];

        $this->namespaces = $this->xml->getDocNamespaces();

        $this->registerNamespace($this->xml);

        $path = explode('\\', get_class($this));
        $path = array_slice($path, -2, 1);
        $this->configs = include $path[0] . '/config.php';
        $firsPart = explode('/', $this->part);
        $part = array_shift($firsPart);
        $updateInfo = $this->xml->xpath('//' . $this->ns . $part . '/@СодержитТолькоИзменения');
        $this->updateInfo = (string)$updateInfo[0] == 'false' ? false : true;
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
            $id = (string)  $item->{$this->configs['key']};
            $this->data[$id] = array();

            $this->registerNamespace($item);

            $this->updateFromConfig($item, $id);
        }
    }

    /**
     * Получение значений для элементов $this->data с использование xpath запросов к node
     * Пути для запросов берутся из конфигурационного файла.
     *
     * @param \SimpleXmlElement $item node - часть выгрузки, на которую будет выполняться запрос
     * @param string $id ключ массива, указанный в конфигурационном файле
     */
    protected function updateFromConfig($item, $id)
    {
        foreach ($this->configs['fields'] as $key => $value) {
            if (is_array($value)) {
                $path = implode('/' . $this->ns, explode('/', $value['path']));
                $path = str_replace('`', $this->ns, $path);
                $value = $key;
            } else {
                $path = $key;
            }
            $needle = $item->xpath($this->ns . $path);

            if (isset($this->configs['fields'][$key]['field']) && is_array($this->configs['fields'][$key]['field'])) {
                foreach ($needle as $node) {
                    $this->registerNamespace($node);

                    $tmp = array();
                    foreach ($this->configs['fields'][$key]['field'] as $name => $conf) {
                        $res = $node->xpath($this->ns . $conf);
                        if (count($res) > 1) {
                            foreach ($res as $val) {
                                $tmp[] = (string) $val;
                            }
                            $this->data[$id][$value] = $tmp;
                            continue;
                        } elseif (count($res) == 1) {
                            $tmp[$name] = (string) $res[0];
                        }
                    }

                    $this->data[$id][$value][] = $tmp;
                }
            } else {
                if (isset($needle[0]) && strlen((string) $needle[0]) != 0) {
                    if (isset($needle[1]) && strlen((string) $needle[1]) != 0 && $key == 'Картинка') {
                        $this->data[$id]['imgs'] = (string) $needle[1];
                    }
                    $this->data[$id][$value] = (string) $needle[0];
                } else {
                    $this->data[$id][$value] = '';
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
