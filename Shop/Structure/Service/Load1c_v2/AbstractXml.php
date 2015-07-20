<?php
namespace Shop\Structure\Service\Load1c_v2;

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

            $namespaces = $item->getDocNamespaces();

            if (isset($namespaces[''])) {
                $defaultNamespaceUrl = $namespaces[''];
                $item->registerXPathNamespace('default', $defaultNamespaceUrl);
            }

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
            $this->data[$id][$value] = (string) $needle[0];
        }
    }
}
