<?php
namespace Shop\Structure\Service\Load1CV208\Models;

use Ideal\Core\Config;
use Shop\Structure\Service\Load1CV208\Db\Tag\DbTag;
use Shop\Structure\Service\Load1CV208\Db\TagMedium\DbTagMedium;
use Shop\Structure\Service\Load1CV208\Xml\Tag\XmlTag;
use Shop\Structure\Service\Load1CV208\Xml\Xml;
use Ideal\Field\Cid\Model as CidModel;

class TegiModel
{
    /** @var array Массив содержащий структурированный ответ по факту обработки файла */
    protected $answer = array(
        'infoText' => 'Обработка тегов из пакета № %d',
        'successText' => 'Добавлено: %d<br />Обновлено: %d',
        'add' => 0,
        'update' => 0
    );

    /**
     * Запуск процесса обработки файлов Tegi_*.xml
     *
     * @param string $filePath Полный путь до обрабатываемого файла
     * @param $packageNum
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($filePath, $packageNum)
    {
        // Определяем пакет для отдачи правильного текста в ответе
        $this->answer['infoText'] = sprintf(
            $this->answer['infoText'],
            $packageNum
        );

        $xml = new Xml($filePath);

        // Инициализируем модель тегов в БД - DbTag
        $dbTag = new DbTag();

        // Инициализируем модель тегов в XML - XmlTag
        $xmlTag = new XmlTag($xml);

        // Устанавливаем связь БД и XML и производим сравнение данных
        $tags = $this->parse($dbTag, $xmlTag);

        // Сохраняем результаты
        $dbTag->save($tags);

        // Данные для medium_mediumlist
        $goodToTag = $xmlTag->tags;

        // Обновление информации в medium_taglist
        $medium = new DbTagMedium();
        $medium->updateTagList($goodToTag);

        return $this->answer();
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
     * Преобразование XML выгрузки в массив и сравнение с данными из БД
     *
     * @param DbTag $dbTag
     * @param XmlTag $xmlTag
     *
     * @return array двумерный массив с данными о ценах после сведения XML и БД
     */
    protected function parse($dbTag, $xmlTag)
    {
        $config = Config::getInstance();
        $part = $config->getStructureByName('Ideal_Tag');
        $cid = new CidModel($part['params']['levels'], $part['params']['digits']);

        // Забираем реззультаты тегов из БД
        $dbResult = $dbTag->parse();

        // Забираем результаты тегов из xml
        $xmlResult = $xmlTag->parse();

        $xmlAddDiff = array_diff_key($xmlResult, $dbResult);
        $this->answer['add'] = count($xmlAddDiff);
        $xmlAddDiff = array_flip(array_keys($xmlAddDiff));
        $this->answer['tmpResult']['tegi']['insert'] = $xmlAddDiff;

        $keys = array();
        $lastCidNum = '001';
        // Перебираем выгрузку из БД и вставляем в xml данные из БД с is_active = 0
        foreach ($dbResult as $key => $element) {
            // Если данные есть в XML - обновляем данные
            if (isset($xmlResult[$key])) {
                // Добавляем поля и информацию из БД в xml
                $xmlResult[$key]['ID'] = $element['ID'];
                $xmlResult[$key]['cid'] = $element['cid'];
            }
            $keys[] = $element['cid'];
            $lastCidNum = $element['cid'];
        }

        $prevCid = '001';
        // Проставляем cid тегам, обновляем поля
        foreach ($xmlResult as $k => $element) {
            if (!isset($element['cid'])) {
                // Если это тег первого уровня, то генерируем cid первого уровня следующий за
                // текущим максимальным из базы данных
                if (intval($element['lvl']) === 1) {
                    $cidNum = $lastCidNum;
                } else {
                    $cidNum = $prevCid;
                }
                $i = 1;
                $fullCid = $cid->setBlock($cidNum, $element['lvl'], $i, true);
                while (in_array($fullCid, $keys)) {
                    $fullCid = $cid->setBlock($cidNum, $element['lvl'], ++$i, true);
                }
                $keys[] = $fullCid;
                $xmlResult[$k]['cid'] = $fullCid;
                $lastCidNum = $fullCid;
            }
            $prevCid = $xmlResult[$k]['cid'];
            if (array_key_exists($k, $dbResult) &&
                count(array_diff_assoc($xmlResult[$k], $dbResult[$k])) === 0
            ) {
                unset($xmlResult[$k]);
            } else {
                if (isset($dbResult[$k]['ID'])) {
                    $xmlResult[$k]['ID'] = $dbResult[$k]['ID'];
                } else {
                    unset($xmlResult[$k]['ID']);
                }
            }
        }

        $xmlUpdateDiff = array_intersect_key($xmlResult, $dbResult);
        $this->answer['update'] = count($xmlUpdateDiff);
        $xmlUpdateDiff = array_flip(array_keys($xmlUpdateDiff));
        $this->answer['tmpResult']['tegi']['update'] = $xmlUpdateDiff;
        return $xmlResult;
    }
}
