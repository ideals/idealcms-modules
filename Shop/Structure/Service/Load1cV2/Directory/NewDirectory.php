<?php
namespace Shop\Structure\Service\Load1cV2\Directory;

use Ideal\Field\Url;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:34
 */
class NewDirectory
{
    /** @var array ответ пользователю об обновленных и добавленных */
    protected $answer = array(
        'step'  => 'Справочники',
        'add'   => 0,
        'update'=> 0,
    );

    /** @var  bool содержит ли xml только обновления */
    protected $onlyUpdate;

    /** @var DbDirectory */
    protected $dbGood;

    /** @var XmlDirectory */
    protected $xmlGood;

    /**
     * @param DbDirectory $dbGood
     * @param XmlDirectory $xmlGood
     */
    public function __construct($dbGood, $xmlGood)
    {
        $this->dbGood = $dbGood;
        $this->xmlGood = $xmlGood;
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
        $diffDb = array_diff(array_keys($dbResult), array_keys($xmlResult));
        foreach ($xmlResult as $k => $val) {
            if (!isset($dbResult[$k])) {
                $result[$k] = $val;
                $this->answer['add']++;
                continue;
            }

            $res = array_diff_assoc($val, $dbResult[$k]);
            if (count($res) > 0) {
                $result[$k] = $res;
                $this->answer['update']++;
            }
        }
/*
 * тут видимо была попытка отключить неиспользуемые поля справочников. Нам это не надо
        foreach ($diffDb as $id) {
            if ($dbResult[$id]['is_active'] == 1) {
                $result[$id]['is_active'] = 0;
                $result[$id]['ID'] = $dbResult[$id]['ID'];
            }
        }
*/
        return $result;
    }
}
