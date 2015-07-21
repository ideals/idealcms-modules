<?php
namespace Shop\Structure\Service\Load1c_v2;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 09.07.2015
 * Time: 19:36
 */

class Xml
{
    public function __construct($source, $file)
    {
        $file_name = realpath($source . '/' . $file);
        $this->xml = simplexml_load_file($file_name);

        $namespaces = $this->xml->getDocNamespaces();

        if (isset($namespaces[''])) {
            $defaultNamespaceUrl = $namespaces[''];
            $this->xml->registerXPathNamespace('default', $defaultNamespaceUrl);
            $this->ns = 'default:';
        }
    }

    public function getPart($className)
    {
        $path = explode('/', $className->part);
        $path = implode('/' . $this->ns, $path);
        $path = str_replace('`', $this->ns, $path);
        return $this->xml->xpath('//' . $this->ns . $path);
    }
}
