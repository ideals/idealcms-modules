<?php
namespace Shop\Structure\Service\Load1c_v2;

use Ideal\Field\Cid\Model;
use Ideal\Core\Config;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:34
 */
class NewCategory
{
    protected $result = array();
    protected $answer = array();
    protected $tmp = array();
    /** @var  DbCategory */
    protected $dbCategory;
    /** @var  XmlCategory */
    protected $xmlCategory;
    protected $lastCid;

    public function __construct($dbCategory, $xmlCategory)
    {
        $this->dbCategory = $dbCategory;
        $this->xmlCategory = $xmlCategory;
    }

    public function parse()
    {
        $config = Config::getInstance();
        $part = $config->getStructureByName('Ideal_Part');
        $cid = new Model($part['params']['levels'], $part['params']['digits']);

        // Забираем реззультаты категорий из БД 1m
        $dbResult = $this->dbCategory->parse();

        // Забираем результаты категорий из xml 1m
        $xmlResult = $this->xmlCategory->parse();

        $this->answer['add'] = array_diff_key($xmlResult, $dbResult);
        // пройти по выгрузке бд и вставить в хмл данные из бд с ис эктив = 0
        foreach ($dbResult as $key => $element) {
            // если в БД not-1c - вставляем элемент к его предку, данные оставляем из выгрузки
            if ($element['id_1c'] == 'not-1c') {
                $parentCid = $cid->getCidByLevel($element['cid'], $element['lvl'] - 1, false);
                $parentCid = $cid->reconstruct($parentCid);
                $parent = $dbResult[$parentCid]['id_1c'];
                $data = array(
                    'ID' => $element['ID'],
                    'parent' => $parent,
                    'is_active' => $element['is_active'],
                    'pos' => $cid->getBlock($element['cid'], $element['lvl']),
                    'Ид' => $element['id_1c'],
                    'Наименование' => $element['name']
                );
                $this->xmlCategory->addChild($data);
            } else {
                // Если данные есть в XML - обновляем данные
                if (isset($xmlResult[$key])) {
                    // добавляем поля информацию из бд в хмл
                    $data = array(
                        'ID' => $element['ID'],
                        'is_active' => $element['is_active'],
                        'pos' => $cid->getBlock($element['cid'], $element['lvl']),
                        'Ид' => $element['id_1c']
                    );
                    $this->xmlCategory->updateElement($data);
                    // Если данных в xml нет - ставим is_active - 0
                } else {
                    $parentCid = $cid->getCidByLevel($element['cid'], $element['lvl'] - 1, false);
                    $parentCid = $cid->reconstruct($parentCid);
                    $parent = $dbResult[$parentCid]['id_1c'];
                    $data = array(
                        'ID' => $element['ID'],
                        'parent' => $parent,
                        'is_active' => '0',
                        'pos' => $cid->getBlock($element['cid'], $element['lvl']),
                        'Ид' => $element['id_1c'],
                        'Наименование' => $element['name']
                    );
                    if ($parent == null) {
                        $data['Наименование'] = $element['name'];
                    }
                    $this->xmlCategory->addChild($data);
                }
            }
        }

        $keys = array();
        $cidNum = '001';
        // получаем обновленную сплющенную выгрузку XML
        $newXmlResult = $this->xmlCategory->parse();
        // проставляем cid категориям
        foreach ($newXmlResult as $k => $element) {
            $i = 1;
            $newXmlResult[$k]['id_1c'] = $element['Ид'];
            $newXmlResult[$k]['name'] = $element['Наименование'];
            unset($newXmlResult[$k]['Ид'], $newXmlResult[$k]['Наименование'], $newXmlResult[$k]['parent']);

            if (isset($element['pos'])) {
                $i = intval($element['pos']);
                $a = $cid->setBlock($cidNum, $element['lvl'], $i, true); // element['pos']

                while (in_array($a, $keys)) {
                    $a = $cid->setBlock($cidNum, $element['lvl'], ++$i, true);
                }

                $cidNum = $a;
                $newXmlResult[$k]['cid'] = $a;
                $keys[] = $a;

                unset($newXmlResult[$k]['pos']);
                if (!is_null($dbResult[$k]) && count(array_diff($newXmlResult[$k], $dbResult[$k])) === 0) {
                    unset($newXmlResult[$k]);
                }
                continue;
            }

            $a = $cid->setBlock($cidNum, $element['lvl'], $i, true);

            while (in_array($a, $keys)) {
                $a = $cid->setBlock($cidNum, $element['lvl'], ++$i, true);
            }
            $keys[] = $a;
            $newXmlResult[$k]['cid'] = $a;
            $cidNum = $a;

            if (!isset($element['is_active'])) {
                $newXmlResult[$k]['is_active'] = '1';
            }

            if (!is_null($dbResult[$k]) && count(array_diff($newXmlResult[$k], $dbResult[$k])) === 0) {
                unset($xmlResult[$k]);
            }
        }

        $this->answer['update'] = array_intersect_key($newXmlResult, $dbResult);
        return $newXmlResult;
        // сравниваем сплющенный и массив из БД и находим delete update add
    }

    public function answer()
    {
        return $this->answer;
    }
}
