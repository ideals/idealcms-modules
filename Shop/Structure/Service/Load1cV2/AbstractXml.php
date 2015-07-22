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
    protected $xml;
    protected $ns;
    protected $configs;
    protected $data;

    public function __construct(Xml $xml)
    {
        $this->xml = $xml->getPart($this);
        $this->xml = $this->xml[0];

        $namespaces = $this->xml->getDocNamespaces();

        if (isset($namespaces[''])) {
            $defaultNamespaceUrl = $namespaces[''];
            $this->xml->registerXPathNamespace('default', $defaultNamespaceUrl);
            $this->ns = 'default:';
        }
        $path = explode('\\', get_class($this));
        $path = array_slice($path, -2, 1);
        $this->configs = include $path[0] . '/config.php';
    }

    public function parse()
    {
        foreach ($this->xml as $item) {
            $id = (string)  $item->{$this->configs['key']};
            $this->data[$id] = array();

            $this->registerNamespace($item);

            $this->updateFromConfig($item, $id);
        }

        return $this->data;
    }

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

            if (is_array($this->configs['fields'][$key]['field'])) {
                foreach ($needle as $node) {
                    $this->registerNamespace($node);

                    $tmp = array();
                    foreach ($this->configs['fields'][$key]['field'] as $name => $conf) {
                        $res = $node->xpath($this->ns . $conf);
                        $tmp[$name] = (string) $res[0];
                    }

                    $this->data[$id][$value][] = $tmp;
                }
            } else {
                if (strlen((string) $needle[0]) != 0) {
                    $this->data[$id][$value] = (string) $needle[0];
                }
            }
        }
    }

    protected function registerNamespace($item)
    {
        $namespaces = $item->getDocNamespaces();

        if (isset($namespaces[''])) {
            $defaultNamespaceUrl = $namespaces[''];
            $item->registerXPathNamespace('default', $defaultNamespaceUrl);
            $this->ns = 'default:';
        }
    }
}
