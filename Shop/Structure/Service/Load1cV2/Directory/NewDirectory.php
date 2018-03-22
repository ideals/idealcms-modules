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
        'infoText'  => 'Справочники',
        'successText'   => 'Добавлено: %d<br />Обновлено: %d',
        'add'   => 0,
        'update'=> 0,
    );

    /** @var  bool содержит ли xml только обновления */
    protected $onlyUpdate;

    /** @var DbDirectory */
    protected $dbDirectory;

    /** @var XmlDirectory */
    protected $xmlDirectory;

    /**
     * @param DbDirectory $dbDirectory
     * @param XmlDirectory $xmlDirectory
     */
    public function __construct($dbDirectory, $xmlDirectory)
    {
        $this->dbDirectory = $dbDirectory;
        $this->xmlDirectory = $xmlDirectory;
    }

    /**
     * Парсинг данных DbGood и XmlGood, и их сравнение
     *
     * @return array разница, которую передаем объекту DbGood для сохранения
     */
    public function parse()
    {
        // Забираем реззультаты категорий из БД 1m
        $dbResult = $this->dbDirectory->parse();

        // Забираем результаты категорий из xml 1m
        $xmlResult = $this->xmlDirectory->parse();

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
                $result[$k] = $val;
                $this->answer['add']++;
                continue;
            }

            $res = array_diff_assoc($val, $dbResult[$k]);
            if (count($res) > 0) {
                $result[$k] = array_merge($dbResult[$k], $res);
                $this->answer['update']++;
            }
        }
        return $result;
    }
}
