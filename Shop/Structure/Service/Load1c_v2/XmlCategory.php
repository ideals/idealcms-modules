<?php
namespace Shop\Structure\Service\Load1c_v2;

use Ideal\Field\Cid\Model;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:32
 */

class XmlCategory
{
    const TYPE_FTP = 1;
    const TYPE_1C = 2;
    const TYPE_LOCAL_PATH = 3;

    public $xml;
    protected $tmpDir;
    protected $ns;
    protected $data;
    /** @var  \Ideal\Field\Cid\Model $cidModel */
    protected $cidModel;
    protected $cid = '001';

    public function __construct($source, $type, $path)
    {
        $this->tmpDir = DOCUMENT_ROOT . '/tmp/1c/';
        if (!file_exists($this->tmpDir)) {
            mkdir($this->tmpDir, 0750, true);
        }

        switch ($type) {
            case self::TYPE_1C:
                $file_name = '';
                break;
            case self::TYPE_FTP:
                if (!$handle = ftp_connect($source)) {
                    // no connection
                }
                $file_name = end(explode('/', $source));
                if (!ftp_get($handle, $this->tmpDir . $file_name, $source, FTP_ASCII)) {
                    // ftp_get не удалось скачать
                }
                ftp_close($handle);
                $file_name = realpath($this->tmpDir . $file_name);
                break;
            case self::TYPE_LOCAL_PATH:
                $file_name = realpath($source);
                if (!file_exists($file_name)) {
                    // no file
                }
                break;
            default:
                throw new \Exception('Неккорректный тип источника');
        }

        $this->xml = simplexml_load_file($file_name);
        $this->setNamespaces();
        $this->setPath($path);
    }

    public function parse()
    {
        $this->cidModel = new Model(6, 3);
        $this->recursiveParse($this->xml);
        return $this->data;
    }

    public function recursiveParse($groupsXML, $lvl = 1)
    {
        $groups = array();
        $i = 1;

        foreach ($groupsXML->{'Группа'} as $child) {
            $id = (string)$child->{'Ид'};
            $this->cid = $this->cidModel->setBlock($this->cid, $lvl, $i);
            $i += 1;
            $this->data[$id] = array(
                'name' => (string)$child->{'Наименование'},
                'lvl' => $lvl,
                'cid' => $this->cid // Cid Model
            );
            if ($child->{'Группы'}) {
                $lvl++;
                $this->recursiveParse($child->{'Группы'}, $lvl);
                $this->cid = $this->cidModel->getCidByLevel($this->cid, --$lvl);
            }
        }
        return $groups;
    }

    protected function setNamespaces()
    {
        $namespaces = $this->xml->getDocNamespaces();

        if (isset($namespaces[''])) {
            $defaultNamespaceUrl = $namespaces[''];
            $this->xml->registerXPathNamespace('default', $defaultNamespaceUrl);
            $this->ns = 'default:';
        }
    }

    protected function setPath($path)
    {
        $path = explode('/', $path);
        $path = implode('/' . $this->ns, $path);
        $tmp = $this->xml->xpath('//' . $this->ns . $path);
        $this->xml = $tmp[0];
        if (0 === $this->xml->count()) {
            return false;
        }

        return true;
    }
}
