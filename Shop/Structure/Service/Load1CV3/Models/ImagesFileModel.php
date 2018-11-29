<?php
namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Service\Load1CV3\Db\ImagesFile\DbImagesFile;
use Shop\Structure\Service\Load1CV3\Xml\Xml;
use Shop\Structure\Service\Load1CV3\Xml\ImagesFile\XmlImagesFile;

class ImagesFileModel
{
    /** @var array Массив содержащий структурированный ответ по факту обработки файла */
    protected $answer = array(
        'infoText' => 'Обработка основных изображений из пакета № %d',
        'successText' => 'Добавлено: %d<br />Обновлено: %d',
        'add' => 0,
        'update' => 0
    );

    /**
     * Запуск процесса обработки файлов ImagesFile_*.xml
     *
     * @param string $filePath Полный путь до обрабатываемого файла
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($filePath)
    {
        // Определяем пакет для отдачи правильного текста в ответе
        $dir = pathinfo($filePath, PATHINFO_DIRNAME);
        $dirParts = explode(DIRECTORY_SEPARATOR, $dir);
        $packageNum = end($dirParts);
        $this->answer['infoText'] = sprintf(
            $this->answer['infoText'],
            $packageNum
        );

        $xml = new Xml($filePath);

        // Инициализируем модель главной картинки в БД - DbImagesFile
        $dbImagesFile = new DbImagesFile();

        // Инициализируем модель главной картинки в XML - XmlImagesFile
        $xmlImagesFile = new XmlImagesFile($xml);

        // Устанавливаем связь БД и XML и производим сравнение данных
        $imagesInfo = $this->parse($dbImagesFile, $xmlImagesFile);

        // Сохраняем результаты
        $dbImagesFile->save($imagesInfo);

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
     * @param DbImagesFile $dbImagesFile
     * @param XmlImagesFile $xmlImagesFile
     *
     * @return array двумерный массив с данными о ценах после сведения XML и БД
     */
    protected function parse($dbImagesFile, $xmlImagesFile)
    {
        $xmlResult = $xmlImagesFile->parse();
        $data = array();
        foreach ($xmlResult as $value) {
            $data[$value['goodId1c']]['img'] = $value['file'];
        }

        // Забираем реззультаты картинок из БД
        $dbImagesFile->setGoodKeys(array_keys($data));
        $dbResult = $dbImagesFile->parse();

        foreach ($dbResult as $key => $value) {
            $imgs = array();
            if (isset($data[$value['id_1c']])) {
                if (!empty($value['imgs'])) {
                    $imgs = json_decode($value['imgs'], true);
                }
                if (!empty($value['img'])) {
                    $imgs[] = $value['img'];
                }
                $entryTmp = substr($data[$value['id_1c']]['img'], 0, 2);
                $checkImg = glob(DOCUMENT_ROOT . "/images/1c/{$entryTmp}/{$data[$value['id_1c']]['img']}*");
                if ($checkImg) {
                    $relativePath = str_replace(DOCUMENT_ROOT, '', $checkImg[0]);
                    if (!in_array($relativePath, $imgs)) {
                        $imgs[] = $relativePath;
                    }
                } else {
                    $data[$value['id_1c']]['img'] = '';
                }
                if (!empty($imgs)) {
                    $mainImgKey = false;
                    foreach ($imgs as $keyItem => $valueItem) {
                        if (stripos($valueItem, $data[$value['id_1c']]['img']) !== false) {
                            $mainImgKey = $keyItem;
                        }
                    }
                    if ($mainImgKey !== false) {
                        $data[$value['id_1c']]['img'] = $imgs[$mainImgKey];
                        unset($imgs[$mainImgKey]);
                        if (!$imgs) {
                            $data[$value['id_1c']]['imgs'] = '';
                        } else {
                            $data[$value['id_1c']]['imgs'] = json_encode($imgs);
                        }
                    }
                }
            }
        }

        $data = array_filter($data);
        return $this->diff($dbResult, $data);
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
                continue;
            }
            $res = array_diff_assoc($val, $dbResult[$k]);
            if (count($res) > 0) {
                $result[$k] = $res;
                $result[$k]['ID'] = $dbResult[$k]['ID'];
                $this->answer['update']++;
                $this->answer['tmpResult']['goods']['insert'][$val['id_1c']] = 1;
            }
        }
        return $result;
    }
}
