<?php
namespace Shop\Structure\Service\Load1cV2\Good;

use Ideal\Field\Url;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:34
 */
class NewGood
{
    /** @var array ответ пользователю об обновленных и добавленных */
    protected $answer = array(
        'step'  => 'Товары',
        'add'   => 0,
        'update'=> 0
    );

    /** @var  bool содержит ли xml только обновления */
    protected $onlyUpdate;

    /** @var DbGood */
    protected $dbGood;

    /** @var XmlGood */
    protected $xmlGood;

    /**
     * @param DbGood $dbGood
     * @param XmlGood $xmlGood
     */
    public function __construct($dbGood, $xmlGood)
    {
        $this->dbGood = $dbGood;
        $this->xmlGood = $xmlGood;
        $this->dbGood->onlyUpdate($this->xmlGood->updateInfo());
    }

    /**
     * Парсинг данных DbGood и XmlGood, и их сравнение
     *
     * @return array разница, которую передаем объекту DbGood для сохранения
     */
    public function parse()
    {
        // Забираем реззультаты категорий из БД 1m
        $dbResult = $this->dbGood->parse();

        // Забираем результаты категорий из xml 1m
        $xmlResult = $this->xmlGood->parse();

        return $this->diff($dbResult, $xmlResult);
    }

    /**
     * Возвращаем ответ пользователю о проделанной работе
     *
     * @return array ответ пользователю 'add'=>count(), 'update'=>count()
     */
    public function answer()
    {
        return $this->answer;
    }

    /**
     * Сравнение результатов выгрузок. Если есть в xml и нет в БД - на добавление
     * Если есть в БД и есть в XML, но есть diff_assoc - добавляем поля для обновления.
     *
     * @param array $dbResult распарсенные данные из БД
     * @param array $xmlResult распарсенные данные из XML
     * @return array разница массивов на обновление и удаление
     */
    protected function diff(array $dbResult, array $xmlResult)
    {
        $result = array();
        foreach ($xmlResult as $k => $val) {
            if (!isset($dbResult[$k])) {
                $this->answer['add']++;
                $result[$k] = $val;
                continue;
            }

            $res = array_diff_assoc($val, $dbResult[$k]);
            if (count($res) > 0) {
                $result[$k] = $res;
                $this->answer['add']++;
            }
        }

        foreach ($dbResult as $id => $dbValue) {
            if (!isset($xmlResult[$id])) {
                if ($dbValue['is_active'] == 1) {
                    $result[$id]['is_active'] = 0;
                    $result[$id]['ID'] = $dbValue['ID'];
                }
                continue;
            }

            $diff = array_diff_assoc($xmlResult[$id], $dbValue);

            if (is_null($diff)) {
                continue;
            }

            // Больше 1 т.к. в xml категория товара представлена его id_1c а в бд выгрузке - ключом ID
            if (count($diff) > 0) {
                $result[$id] = $diff;
                $result[$id]['ID'] = $dbValue['ID'];
            }
        }

        $this->answer['update'] = count($result) - $this->answer['add'];
        return $result;
    }
}
