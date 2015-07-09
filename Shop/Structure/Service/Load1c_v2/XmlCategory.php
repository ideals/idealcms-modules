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
        $namespaces = $this->xml->getDocNamespaces();

        if (isset($namespaces[''])) {
            $defaultNamespaceUrl = $namespaces[''];
            $this->xml->registerXPathNamespace('default', $defaultNamespaceUrl);
            $this->ns = 'default:';
        }
//        $this->xml = $this->xml->xpath('//' . $this->ns . 'Классификатор/' . $this->ns . 'Группы');
    }

    public function parse()
    {
        $this->recursiveParse($this->xml->{'Классификатор'}->{'Группы'});
        return $this->data;
    }

    public function updateElement($array)
    {
        $path = '//' . $this->ns . '*[' . $this->ns . 'Ид="' . $array['Ид'] . '"]';
        unset($array['id_1c']);
        $element = $this->xml->xpath($path);

        foreach ($array as $key => $value) {
            if (!isset($element[0]->{$key})) {
                $element[0]->addChild($key, $value);
            }
        }
    }

    public function addChild($array)
    {
        $path = '//' . $this->ns . 'Группа';
        if ($array['parent'] != null) {
            $path .= '[' . $this->ns . 'Ид="' . $array['parent'] . '"]/' . $this->ns . 'Группы';
        } else {
            $path = '//' . $this->ns . 'Классификатор/' . $this->ns . 'Группы';
        }
        $parent = $this->xml->xpath($path);

        $element = $parent[0]->addChild('Группа');
        unset($array['parent']);

        foreach ($array as $key => $value) {
            $element->addChild($key, $value);
        }
    }

    protected function recursiveParse($groupsXML, $i = 0, $parent = '', $lvl = 1)
    {
        $groups = array();

        foreach ($groupsXML->{'Группа'} as $child) {
            if ((string)$child->{'Ид'} == 'not-1c') {
                $id = $i++;
            } else {
                $id = (string)$child->{'Ид'};
            }
            if ($parent != '') {
                $this->data[$id]['parent'] = $parent;
            }
            $this->data[$id]['lvl'] = $lvl;
            foreach ($child as $key => $field) {
                if ($key != 'Группы') {
                    $this->data[$id][(string) $key] = (string) $field;
                }
            }
            if ($child->{'Группы'}) {
                $this->recursiveParse($child->{'Группы'}, $i, $id, ++$lvl);
                $lvl--;
            }
        }
        return $groups;
    }
}
