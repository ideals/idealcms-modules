<?php
namespace Shop\Structure\Service\Load1cV2\Category;

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
    /** @var array ответ о добавленных и удаленных категориях */
    protected $answer = array(
        'infoText' => 'Обработка категорий/групп товаров',
        'successText'   => 'Добавлено: %d<br />Обновлено: %d',
    );

    /** @var  bool содержит ли xml только обновления */
    protected $onlyUpdate;

    /** @var  DbCategory объект модели БД */
    protected $dbCategory;

    /** @var  XmlCategory объект модели XML */
    protected $xmlCategory;

    /**
     * @param DbCategory $dbCategory объект категорий БД
     * @param XmlCategory $xmlCategory объект категорий XML
     */
    public function __construct($dbCategory, $xmlCategory)
    {
        $this->dbCategory = $dbCategory;
        $this->xmlCategory = $xmlCategory;
    }

    /**
     * Преобразование XML выгрузки в массив и сравнение с данными из БД
     *
     * @return array двумерный массив с данными о категориях после сведения XML и БД
     */
    public function parse()
    {
        $config = Config::getInstance();
        $part = $config->getStructureByName('Ideal_Part');
        $cid = new Model($part['params']['levels'], $part['params']['digits']);

        // Забираем реззультаты категорий из БД 1m
        $dbResult = $this->dbCategory->parse();

        // Забираем результаты категорий из xml 1m
        $xmlResult = $this->xmlCategory->parse();

        $this->answer['add'] = count(array_diff_key($xmlResult, $dbResult));
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
                    $parent = $this->dbCategory->getParentByCid($parentCid);
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

        unset($xmlResult, $data, $element);

        $keys = array();
        $cidNum = '001';
        // получаем обновленную сплющенную выгрузку XML
        $this->xmlCategory->updateConfigs();
        $newXmlResult = $this->xmlCategory->parse();
        // проставляем cid категориям, обновляем поля
        foreach ($newXmlResult as $k => $element) {
            $i = 1;

            if (isset($element['pos']) && $element['pos'] != '') {
                $i = intval($element['pos']);
                $fullCid = $cid->setBlock($cidNum, $element['lvl'], $i, true); // element['pos']

                while (in_array($fullCid, $keys)) {
                    $fullCid = $cid->setBlock($cidNum, $element['lvl'], ++$i, true);
                }

                $cidNum = $fullCid;
                $newXmlResult[$k]['cid'] = $fullCid;
                $keys[] = $fullCid;

                unset($newXmlResult[$k]['pos']);
                if (!is_null($dbResult[$k]) && count(array_diff($newXmlResult[$k], $dbResult[$k])) === 0) {
                    unset($newXmlResult[$k]);
                }
                continue;
            }

            $fullCid = $cid->setBlock($cidNum, $element['lvl'], $i, true);

            while (in_array($fullCid, $keys)) {
                $fullCid = $cid->setBlock($cidNum, $element['lvl'], ++$i, true);
            }
            $keys[] = $fullCid;
            $newXmlResult[$k]['cid'] = $fullCid;
            $cidNum = $fullCid;

            if (!isset($element['is_active']) || $element['is_active'] == '') {
                $newXmlResult[$k]['is_active'] = '0';
            }

            unset($newXmlResult[$k]['pos']);

            if (array_key_exists($k, $dbResult) &&
                count(array_diff($newXmlResult[$k], $dbResult[$k])) === 0) {
                unset($newXmlResult[$k]);
            } else {
                if (isset($dbResult[$k]['ID'])) {
                    $newXmlResult[$k]['ID'] = $dbResult[$k]['ID'];
                } else {
                    unset($newXmlResult[$k]['ID']);
                }
            }
        }

        $this->answer['update'] = count(array_intersect_key($newXmlResult, $dbResult));
        return $newXmlResult;
    }

    /**
     * Геттер
     *
     * @return array массив об обновленных и добавленных категориях
     */
    public function answer()
    {
        $this->answer['successText'] = sprintf(
            $this->answer['successText'],
            $this->answer['add'],
            $this->answer['update']
        );
        return $this->answer;
    }
}
