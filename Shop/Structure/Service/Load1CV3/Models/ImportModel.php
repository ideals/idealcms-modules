<?php
namespace Shop\Structure\Service\Load1CV3\Models;

use Ideal\Core\Config;
use Ideal\Field\Cid\Model as CidModel;
use Shop\Structure\Service\Load1CV3\Db\Category\DbCategory;
use Shop\Structure\Service\Load1CV3\Db\Good\DbGood;
use Shop\Structure\Service\Load1CV3\Db\Medium\DbMedium;
use Shop\Structure\Service\Load1CV3\Image;
use Shop\Structure\Service\Load1CV3\Xml\Good\XmlGood;
use Shop\Structure\Service\Load1CV3\Xml\Xml;
use Shop\Structure\Service\Load1CV3\Xml\Category\XmlCategory;
use Shop\Structure\Service\Load1CV3\Xml\Stock\XmlStock;

class ImportModel
{

    /** @var array Массив содержащий структурированный ответ по факту обработки файла */
    protected $answer = array(
        'infoText' => 'Обработка товаров из пакета № %d',
        'successText' => 'Добавлено: %d<br />Обновлено: %d',
        'add' => 0,
        'update' => 0
    );

    protected $packageNum = 0;

    /** @var array Общие настройки для всего процесса обмена */
    public $exchangeConfig = array();

    public function __construct($exchangeConfig)
    {
        $this->exchangeConfig = $exchangeConfig;
    }

    /**
     * Запуск процесса обработки файлов import_*.xml
     *
     * @param string $filePath Полный путь до обрабатываемого файла
     * @param int $packageNum Номер пакета
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($filePath, $packageNum)
    {
        // Файл import_*.xml может быть двух типов.
        // Корневой содержит информацию о структуре каталога, свойствах хранящихся в справочниках, типах цен и
        // еденицах измерения количества.
        // Пакетный, содержит бОльшую часть информации о товарах.
        // Определяем тип файла и в зависимости от этого запускаем нужну обработку.
        $this->packageNum = $packageNum;
        $xml = new Xml($filePath);
        $xmlCategory = new XmlCategory($xml);
        if ($xmlCategory->validate()) {
            // Это корневой файл import_*.xml
            $this->category($filePath);
        } else {
            $this->good($filePath);
        }
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
     * Обновление категорий из xml выгрузки
     *
     * @param string $filePath путь до одного из основных файлов выгрузки
     * @return bool Флг (не)успешного завершения работы метода
     */
    protected function category($filePath)
    {
        $xml = new Xml($filePath);

        // Получаем идентификатор основного склада
        $xmlStock = new XmlStock($xml);
        $mainStockId = $xmlStock->getXml();
        if (empty($mainStockId)) {
            $this->answer['status'] = 'failure';
            $this->answer['infoText'] = 'Ошибка';
            $this->answer['successText'] = 'Ошибка определения основного склада, нет данных';
            return false;
        }
        $this->answer['tmpResult']['mainStockId'] = (string)$mainStockId;

        // инициализируем модель категорий в XML - XmlCategory
        $xmlCategory = new XmlCategory($xml);

        $xmlCategoryXml = $xmlCategory->getXml();

        if (!empty($xmlCategoryXml)) {
            // инициализируем модель категорий в БД - DbCategory
            $dbCategory = new DbCategory();

            // Устанавливаем связь БД и XML
            $this->categoryParse($dbCategory, $xmlCategory);

            // Создание категории товаров, у которых в выгрузке не присвоена категория
            $dbCategory->createDefaultCategory();
        }
        $this->answer['infoText'] = 'Обработка разделов каталога';
    }

    /**
     * Преобразование XML выгрузки в массив и сравнение с данными из БД
     *
     * @param DbCategory $dbCategory объект категорий БД
     * @param XmlCategory $xmlCategory объект категорий XML
     */
    protected function categoryParse($dbCategory, $xmlCategory)
    {
        $config = Config::getInstance();
        $part = $config->getStructureByName('Ideal_Part');
        $cid = new CidModel($part['params']['levels'], $part['params']['digits']);

        // Собираем данные по категориям из xml
        $xmlResult = $xmlCategory->parse();

        // Собираем данные по категориям из БД
        $dbCategory->setCategoryKeys(array_keys($xmlResult));
        $dbResult = $dbCategory->parse();

        // Проходим по выгрузке бд и вставляем в xml данные из бд с is_active = 0
        foreach ($dbResult as $key => $dbElement) {
            // Если в БД not-1c - вставляем элемент к его предку, данные оставляем из БД
            if ($dbElement['id_1c'] === 'not-1c') {
                $parentCid = $cid->getCidByLevel($dbElement['cid'], $dbElement['lvl'] - 1, false);
                $parentCid = $cid->reconstruct($parentCid);
                $parent = $dbResult[$parentCid]['id_1c'];
                $data = array(
                    'ID' => $dbElement['ID'],
                    'parent' => $parent,
                    'is_active' => $dbElement['is_active'],
                    'pos' => $cid->getBlock($dbElement['cid'], $dbElement['lvl']),
                    'Ид' => $dbElement['id_1c'],
                    'Наименование' => $dbElement['name']
                );
                $xmlCategory->addChild($data);
            } elseif (isset($xmlResult[$key])) {
                // Добавляем информацию из бд в xml
                $data = array(
                    'ID' => $dbElement['ID'],
                    'pos' => $cid->getBlock($dbElement['cid'], $dbElement['lvl']),
                    'Ид' => $dbElement['id_1c']
                );
                $xmlCategory->updateElement($data);
            }
        }

        $keys = array();
        $cidNum = '001';
        // Получаем обновленную сплющенную информацию по категориям из XML
        $xmlCategory->updateConfigs();
        $newXmlResult = $xmlCategory->parse();

        // Проставляем cid категориям, обновляем поля
        foreach ($newXmlResult as $k => $xmlElement) {
            $i = 1;
            if (isset($xmlElement['pos']) && $xmlElement['pos'] !== '') {
                $i = (int)$xmlElement['pos'];
            }
            $fullCid = $cid->setBlock($cidNum, $xmlElement['lvl'], $i, true);
            while (in_array($fullCid, $keys)) {
                $fullCid = $cid->setBlock($cidNum, $xmlElement['lvl'], ++$i, true);
            }
            $cidNum = $fullCid;
            $xmlElement['cid'] = $fullCid;
            $keys[] = $fullCid;
            unset($xmlElement['pos']);

            // Если идентичная запись уже есть в БД, то переходим к рассмотрению следующего элемента
            if (array_key_exists($k, $dbResult) && count(array_diff_assoc($xmlElement, $dbResult[$k])) === 0) {
                continue;
            }

            if (!isset($xmlElement['is_active']) || $xmlElement['is_active'] == '') {
                $xmlElement['is_active'] = '0';
            }

            if (isset($dbResult[$k])) {
                // Если запись уже существует в базе, то обновляем её
                $xmlElement['ID'] = $dbResult[$k]['ID'];
                $this->answer['update']++;
                $dbCategory->update($xmlElement, $dbResult[$k]);
                $this->answer['tmpResult']['category']['update'][$xmlElement['id_1c']] = 1;
            } else {
                // Если это новая запись, то записываем её в БД
                unset($xmlElement['ID']);
                $this->answer['add']++;
                $dbCategory->insert($xmlElement);
                $this->answer['tmpResult']['category']['insert'][$xmlElement['id_1c']] = 1;
            }
        }
    }

    /**
     * Обновление товаров из выгрузки
     *
     * @param string $filePath путь до одного из файлов выгрузки
     */
    protected function good($filePath)
    {
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

        // Обработка изображений переданых вместе с товарами
        $dir = pathinfo($filePath, PATHINFO_DIRNAME);
        $pictDirectory = $dir . DIRECTORY_SEPARATOR . $this->exchangeConfig['images_directory'];
        $this->loadImages($pictDirectory);
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

        // Забираем реззультаты товаров из БД
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
                $dbGood->onAfterSetDbElement(array(), $val);
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
