<?php
namespace Shop\Structure\Service\Load1CV208\Xml;

class Xml
{
    /** SimpleXMLElement Данные от 1С */
    private $xml ;

    public function __construct($source)
    {
        libxml_use_internal_errors(true);
        if (!file_exists($source)) {
            throw new \RuntimeException('Отсутствует файл выгрузки');
        }
        $this->xml = simplexml_load_string(file_get_contents($source));

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
        if (isset($this->ns)) {
            $path = implode('/' . $this->ns, $path);
            $path = str_replace('`', $this->ns, $path);
            $path = $this->ns . $path;
        } else {
            $path = implode('/', $path);
        }
        return $this->xml->xpath('//' . $path);
    }
}
