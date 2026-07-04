<?php

namespace Shop\Structure\Service\Load1CV208\Models;

use Shop\Structure\Service\Load1CV208\Db\ImagesFile\DbImagesFile;
use Shop\Structure\Service\Load1CV208\Xml\Xml;
use Shop\Structure\Service\Load1CV208\Xml\ImagesFile\XmlImagesFile;

class ImagesFileModel
{
    /** @var array Массив содержащий структурированный ответ по факту обработки файла */
    protected $answer = [
        'infoText' => 'Обработка основных изображений из пакета № %d',
        'successText' => 'Добавлено: %d<br />Обновлено: %d',
        'add' => 0,
        'update' => 0,
    ];

    /**
     * Запуск процесса обработки файлов ImagesFile_*.xml
     *
     * @param string $filePath Полный путь до обрабатываемого файла
     * @param int $packageNum Номер пакета
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($filePath, $packageNum)
    {
        // Определяем пакет для отдачи правильного текста в ответе
        $this->answer['infoText'] = sprintf(
            $this->answer['infoText'],
            $packageNum,
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
            $this->answer['update'],
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
    protected function parse($dbImagesFile, $xmlImagesFile): array
    {
        $xmlResult = $xmlImagesFile->parse();
        $data = [];
        foreach ($xmlResult as $value) {
            $data[$value['goodId1c']]['img'] = $value['file'];
        }

        // Забираем реззультаты картинок из БД
        $dbImagesFile->setGoodKeys(array_keys($data));
        $dbResult = $dbImagesFile->parse();

        foreach ($dbResult as $value) {
            $id1c = $value['id_1c'];
            $imgs = [];
            if (isset($data[$id1c])) {
                if (!empty($value['imgs'])) {
                    $imgs = json_decode($value['imgs'], true);
                }

                if (!empty($value['img'])) {
                    $imgs[] = $value['img'];
                }

                $entryTmp = substr($data[$id1c]['img'], 0, 2);
                $checkImg = glob(DOCUMENT_ROOT . sprintf('/images/1c/%s/%s*', $entryTmp, $data[$id1c]['img']));
                if ($checkImg) {
                    $relativePath = str_replace(DOCUMENT_ROOT, '', $checkImg[0]);
                    if (!in_array($relativePath, $imgs)) {
                        $imgs[] = $relativePath;
                    }
                } else {
                    $data[$id1c]['img'] = '';
                }

                if (!empty($imgs)) {
                    $mainImgKey = false;
                    foreach ($imgs as $keyItem => $valueItem) {
                        if (empty($data[$id1c]['img']) || stripos($valueItem, (string) $data[$id1c]['img']) === false) {
                            continue;
                        }

                        $mainImgKey = $keyItem;
                    }

                    if ($mainImgKey !== false) {
                        $data[$id1c]['img'] = $imgs[$mainImgKey];
                        unset($imgs[$mainImgKey]);
                        $data[$id1c]['imgs'] = $imgs ? json_encode($imgs) : '';
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
     * @param array<mixed, array<string, mixed>> $xmlResult распарсенные данные из XML
     * @return array разница массивов на обновление и удаление
     */
    protected function diff(array $dbResult, array $xmlResult): array
    {
        $result = [];
        foreach ($xmlResult as $k => $val) {
            if (!isset($dbResult[$k])) {
                continue;
            }

            $res = array_diff_assoc($val, $dbResult[$k]);
            if ($res !== []) {
                $result[$k] = $res;
                $result[$k]['ID'] = $dbResult[$k]['ID'];
                $this->answer['update']++;
                $this->answer['tmpResult']['goods']['insert'][$k] = 1;
            }
        }

        return $result;
    }
}
