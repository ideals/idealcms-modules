<?php
namespace Shop\Structure\Service\Load1c_v2;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:32
 */

class XmlCategory
{
    public $xml;
    protected $ns;
    protected $data;

    public function __construct($source)
    {
        $file_name = realpath($source . '/import.xml');

        $this->xml = simplexml_load_file($file_name);
        $this->setNamespaces();
        $namespaces = $this->xml->getDocNamespaces();

        if (isset($namespaces[''])) {
            $defaultNamespaceUrl = $namespaces[''];
            $this->xml->registerXPathNamespace('default', $defaultNamespaceUrl);
            $this->ns = 'default:';
        }
        $this->xml = $this->xml->xpath('//' . $this->ns . 'Классификатор/' . $this->ns . 'Группы');
    }

    public function parse()
    {
        $this->recursiveParse($this->xml[0]);
        return $this->data;
    }

    public function updateElement($array)
    {
        $path = '//' . $this->ns . '*[' . $this->ns . 'Ид="' . $array['Ид'] . '"]';
        unset($array['id_1c']);
        $element = $this->xml[0]->xpath($path);

        foreach ($array as $key => $value) {
            $element->addChild($key, $value);
        }
    }

    public function addChild($array)
    {
        $path = '//' . $this->ns . 'Группа';
        if ($array['parent'] != null) {
            $path .= '[' . $this->ns . 'Ид="' . $array['parent'] . '"]/' . $this->ns . 'Группы';
        }
        $parent = $this->xml[0]->xpath($path);
        $element = $parent->addChild('Группа');
        unset($array['parent']);

        foreach ($array as $key => $value) {
            $element->addChild($key, $value);
        }
    }

    public function recursiveParse($groupsXML, $lvl = 1)
    {
        $groups = array();
        $i = 1;

        foreach ($groupsXML->{'Группа'} as $child) {
            $id = (string)$child->{'Ид'};
            $i += 1;
            $this->data[$id] = array(
                'name' => (string)$child->{'Наименование'},
                'lvl' => $lvl,
            );
            if ($child->{'Группы'}) {
                $lvl++;
                $this->recursiveParse($child->{'Группы'}, $lvl--);
            }
        }
        return $groups;
    }

    protected function setNamespaces()
    {

    }
}
