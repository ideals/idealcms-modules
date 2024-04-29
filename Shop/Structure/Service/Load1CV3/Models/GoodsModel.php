<?php
namespace Shop\Structure\Service\Load1CV3\Models;

use Shop\Structure\Service\Load1CV3\Db\Good\DbGood;
use Shop\Structure\Service\Load1CV3\Db\Medium\DbMedium;
use Shop\Structure\Service\Load1CV3\Image;
use Shop\Structure\Service\Load1CV3\ModelAbstract;
use Shop\Structure\Service\Load1CV3\Xml\Good\XmlGood;
use Shop\Structure\Service\Load1CV3\Xml\Xml;

class GoodsModel extends ModelAbstract
{
    public function init(): void
    {
        $this->setInfoText('Обработка товаров (goods)');
        $this->setSort(80);
    }

    /**
     * Запуск процесса обработки файлов propertiesGoods_*.xml
     *
     * @param string $filePath Полный путь до обрабатываемого файла
     * @param int $packageNum Номер пакета
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($filePath, $packageNum): array
    {
        $this->filename = $filePath;
        $this->packageNum = $packageNum;

        // Обработка изображений переданных вместе с товарами
        $dir = pathinfo($filePath, PATHINFO_DIRNAME);
        $pictDirectory = $dir . DIRECTORY_SEPARATOR . $this->exchangeConfig['images_directory'];
        $this->loadImages($pictDirectory);

        // Определяем пакет для отдачи правильного текста в ответе
        $this->answer['infoText'] = sprintf(
            $this->answer['infoText'],
            $this->packageNum
        );

        $xml = new Xml($filePath);

        // Инициализируем модель товаров в БД - DbGood
        $dbGood = new DbGood();

        // Инициализируем модель товаров в XML - XmlGood
        $xmlGood = new XmlGood($xml);

        // Устанавливаем связь БД и XML и производим сравнение данных
        $this->goodParse($dbGood, $xmlGood);

        // Данные для medium_categorylist
        $groups = $xmlGood->groups;

        // Обновление информации в medium_categorylist
        $medium = new DbMedium();
        $medium->updateCategoryList($groups);

        return $this->answer();
    }

    /**
     * Распределение изображений, находящихся в директории с выгрузкой. Оригинальные файлы удаляются
     *
     * @param string $pictDirectory Полный путь до папки с изображениями
     * @param bool $onlyImageResize Флаг запуска только ресайза картинок
     *
     * @return bool признак (не)успешного завершения работы метода
     */
    public function loadImages($pictDirectory, $onlyImageResize = false)
    {
        if ($onlyImageResize) {
            $this->answer['successText'] = '';
            // Определяем пакет для отдачи правильного текста в ответе
            $this->answer['infoText'] = sprintf(
                'Обработка картинок из пакета %d',
                $this->packageNum
            );
        }

        if (!file_exists($pictDirectory)) {
            $this->answer['successText'] .= '<br />Картинки для обработки отсутствуют в данном пакете';
            return false;
        }

        $processedImg = 0;
        $handle = opendir($pictDirectory);

        if (isset($this->exchangeConfig['resize']) && !empty($this->exchangeConfig['resize'])) {
            list($w, $h) = explode('x', $this->exchangeConfig['resize']);
        }

        while (false !== ($entry = readdir($handle))) {
            if (0 === strpos($entry, '.')) {
                continue;
            }

            if (is_dir($pictDirectory . $entry)) {
                $incHandle = opendir($pictDirectory . $entry);

                while (false !== ($img = readdir($incHandle))) {
                    if (0 === strpos($img, '.')) {
                        continue;
                    }
                    $imgExtension = pathinfo($img, PATHINFO_EXTENSION);
                    if (stripos($this->exchangeConfig['supportedExtensionsImage'], $imgExtension) !== false) {
                        $path = $pictDirectory . $entry . '/' . $img;
                        if (isset($w, $h) && !empty($w) && !empty($h)) {
                            new Image($path, $w, $h);
                        } else {
                            $image = basename($img);
                            $entryTmp = substr($image, 0, 2);
                            $concurrDir = DOCUMENT_ROOT . "/images/1c/{$entryTmp}";
                            /** @noinspection MkdirRaceConditionInspection */
                            if (!is_dir($concurrDir) && !mkdir($concurrDir, 0750, true)) {
                                throw new \RuntimeException(sprintf('Не удалось создать директорию "%s"', $concurrDir));
                            }

                            copy($path, DOCUMENT_ROOT . "/images/1c/{$entryTmp}/{$img}");
                        }
                        $processedImg++;
                        unlink($path);
                    }
                }

                closedir($incHandle);
                $iterator = new \FilesystemIterator($pictDirectory . $entry);
                $isDirEmpty = !$iterator->valid();
                if ($isDirEmpty) {
                    rmdir($pictDirectory . $entry);
                }
            } else {
                $imgExtension = pathinfo($entry, PATHINFO_EXTENSION);
                if (stripos($this->exchangeConfig['supportedExtensionsImage'], $imgExtension) !== false) {
                    $path = $pictDirectory . $entry;
                    if (isset($w, $h) && !empty($w) && !empty($h)) {
                        new Image($path, $w, $h);
                    } else {
                        $image = basename($entry);
                        $entryTmp = substr($image, 0, 2);

                        $concurrDir = DOCUMENT_ROOT . "/images/1c/{$entryTmp}";
                        /** @noinspection MkdirRaceConditionInspection */
                        if (!is_dir($concurrDir) && !mkdir($concurrDir, 0750, true)) {
                            throw new \RuntimeException(sprintf('Не удалось создать директорию "%s"', $concurrDir));
                        }

                        copy($path, DOCUMENT_ROOT . "/images/1c/{$entryTmp}/{$entry}");
                    }
                    $processedImg++;
                    unlink($path);
                }
            }
        }
        if ($processedImg !== 0) {
            $this->answer['successText'] .= '<br />Обработано картинок - ' . $processedImg;
        } else {
            $this->answer['successText'] .= '<br />Картинки пригодные для обработки отсутствуют в данном пакете';
        }
        return true;
    }

    /**
     * Парсинг данных DbGood и XmlGood, и их сравнение
     *
     * @param DbGood $dbGood
     * @param XmlGood $xmlGood
     */
    protected function goodParse($dbGood, $xmlGood)
    {
        // Забираем результаты товаров из xml
        $xmlResult = $xmlGood->parse();

        // Забираем результаты товаров из БД
        $dbGood->setGoodKeys(array_keys($xmlResult));
        $dbResult = $dbGood->parse();

        $this->diff($dbResult, $xmlResult, $dbGood);
    }

    /**
     * Сравнение результатов выгрузок. Если есть в xml и нет в БД - добавление
     * Если есть в БД и есть в XML, но есть diff_assoc - обновление.
     *
     * @param array $dbResult распарсенные данные из БД
     * @param array $xmlResult распарсенные данные из XML
     * @param DbGood $dbGood Объект для работы с товарами в БД
     */
    protected function diff(array $dbResult, array $xmlResult, $dbGood)
    {
        foreach ($xmlResult as $k => $val) {
            if (!isset($dbResult[$k])) {
                $this->answer['add']++;
                $val['ID'] = $dbGood->insert($val);
                $this->answer['tmpResult']['goods']['insert'][$val['id_1c']] = 1;
                $dbGood->onAfterSetDbElement($val, $val);
                continue;
            }

            $res = array_diff_assoc($val, $dbResult[$k]);
            if (count($res) > 0) {
                $val['ID'] = $res['ID'] = $dbResult[$k]['ID'];
                $this->answer['update']++;
                $dbGood->update($res, $dbResult[$k]);
                $this->answer['tmpResult']['goods']['update'][$val['id_1c']] = 1;
                $dbGood->onAfterSetDbElement($dbResult[$k], $val);
            }
        }
    }
}
