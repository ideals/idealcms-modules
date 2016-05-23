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
        'infoText' => 'Обработка товаров',
        'successText'   => 'Добавлено: %d<br />Обновлено: %d',
        'add'           => 0,
        'update'        => 0
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
    }

    /**
     * Парсинг данных DbGood и XmlGood, и их сравнение
     *
     * @return array разница, которую передаем объекту DbGood для сохранения
     */
    public function parse()
    {
        // Забираем реззультаты товаров из БД 1m
        $dbResult = $this->dbGood->parse();

        // Забираем результаты товаров из xml 1m
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
        $this->answer['successText'] = sprintf(
            $this->answer['successText'],
            $this->answer['add'],
            $this->answer['update']
        );
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
                $result[$k]['ID'] = $dbResult[$k]['ID'];
                $this->answer['update']++;
            }
        }
        return $result;
    }
}
