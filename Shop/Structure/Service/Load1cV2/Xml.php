<?php
namespace Shop\Structure\Service\Load1cV2;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 09.07.2015
 * Time: 19:36
 */

class Xml
{
    public function __construct($source)
    {
        libxml_use_internal_errors(true);
        if (!file_exists($source)) {
            throw new \RuntimeException("Отсутствует файл выгрузки");
        }
        $this->xml = simplexml_load_file($source);

        if (false === $this->xml) {
            $errors = '';
            foreach (libxml_get_errors() as $error) {
                $errors .= $error->message;
            }
            throw new \RuntimeException("Во время загрузки файла: {$source} \nвозникли следующие ошибки: {$errors}");
        }
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
