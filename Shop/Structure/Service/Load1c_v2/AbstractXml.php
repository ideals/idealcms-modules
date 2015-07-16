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
    }
}
