<?php
namespace Shop\Structure\Service\Load1CV3;

use Ideal\Core\Db;
use Shop\Structure\Service\Load1CV3\Db\Category\DbCategory;
use Shop\Structure\Service\Load1CV3\Db\Directory\DbDirectory;
use Shop\Structure\Service\Load1CV3\Db\Good\DbGood;
use Shop\Structure\Service\Load1CV3\Db\Medium\DbMedium;
use Shop\Structure\Service\Load1CV3\Db\Offer\DbOffer;
use Shop\Structure\Service\Load1CV3\Db\Order\DbOrder;
use Shop\Structure\Service\Load1CV3\Db\Oplata\DbOplata;
use Shop\Structure\Service\Load1CV3\Db\Tag\DbTag;
use Shop\Structure\Service\Load1CV3\Db\TagMedium\DbTagMedium;
use Shop\Structure\Service\Load1CV3\Db\Storage\DbStorage;
use Shop\Structure\Service\Load1CV3\Db\Unit\DbUnit;
use Shop\Structure\Service\Load1CV3\Xml\Category\XmlCategory;
use Shop\Structure\Service\Load1CV3\Xml\Order\XmlOrder;
use Shop\Structure\Service\Load1CV3\Xml\Xml;

class ExchangeUtil
{
    /**
     * Метод рекурсивного удаления файлов и директорий
     *
     * @param string|null $path абсолютный путь к директории, которую нужно удалить вместе с содержимым
     */
    public static function purge($path)
    {
        if (is_dir($path)) {
            $path = realpath($path);
            if (substr($path, -1) != DIRECTORY_SEPARATOR) {
                $path .= DIRECTORY_SEPARATOR;
            }
            if ($dh = opendir($path)) {
                while (($file = readdir($dh)) !== false) {
                    if (strpos($file, '.') === 0) {
                        continue;
                    }
                    self::purge($path . $file);
                }
                closedir($dh);
            }
            rmdir($path);
        } else {
            unlink($path);
        }
    }

    /**
     * Сохраняет файл из потока
     *
     * @param string $filename Полный путь до места сохранения файла
     * @param string $mode Тип доступа
     */
    public static function saveFileFromStream($filename, $mode)
    {
        $f = fopen($filename, $mode);
        fwrite($f, file_get_contents('php://input'));
        fclose($f);
    }

    /**
     * Пытается создать директорию по указанному пути
     * @param string $directory Полный путь до новой директории
     */
    public static function createFolder($directory)
    {
        /** @noinspection MkdirRaceConditionInspection */
        if (!is_dir($directory) && !mkdir($directory, 0750, true)) {
            throw new \RuntimeException(sprintf('Не удалось создать директорию "%s" ', $directory));
        }
    }

    /**
     * Проверяет надобность добавления передаваемого файла к уже существующему
     *
     * @param string $filename Полный путь до места сохранения файла
     * @return bool Признак надобности добавления передаваемого файла к уже существующему
     */
    public static function checkNeedingAdd($filename)
    {
        if (!file_exists($filename)) {
            return false;
        }

        $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
        // Если файл уже присутствует и это не xml файл то точно требуется дозаписать
        if ($fileExtension != 'xml') {
            return true;
        }

        // Если у передаваемого xml файла нет стартового тега то явно требуется дозаписать уже существующий
        $fileFromStream = file_get_contents('php://input');
        if (strpos($fileFromStream, '<?xml') === false) {
            return true;
        }

        return false;
    }


    /**
     * Переносит файлы из одной директории в другую причём в конечной директории файлы выстраиваются в структуру
     * идентичную выгрузке из 1С
     *
     * @param string $fromDir Директория из которой необходимо переместить файлы
     * @param string $toDir Директория в которую будут перемещены файлы
     * @param bool $filesFirst Признак того что сперва нужно переносить файлы
     * (Нужно для переноса картинок в правильный пакет)
     */
    public static function transferFilesWthStructureSaving($fromDir, $toDir, $filesFirst = true)
    {
        $dir = new \DirectoryIterator($fromDir);
        foreach ($dir as $item) {
            if ($item->isDot()) {
                continue;
            }
            if ($filesFirst && $item->isDir()) {
                continue;
            }
            if ($item->isDir()) {
                $lastPackageFolder = self::getLastPackageFolder($toDir);
                // Если найдена папка, то это картинки, других папок быть не может.
                // Переносим всю папку в папку последнего пакета в директории назначения
                self::recurseCopy($fromDir, $lastPackageFolder);
                $path = $item->getPathname();
                self::purge($path . DIRECTORY_SEPARATOR);
            }
            if ($item->isFile()) {
                $path = $item->getPathname();
                self::moveFile($path, $toDir);
            }
        }
        if ($filesFirst) {
            self::transferFilesWthStructureSaving($fromDir, $toDir, false);
        }
    }

    /**
     * Ищет название папки последнего пакета в указанной директории
     *
     * @param string $dirToSearch Полный путь до директории в которой нужно искать папку пакета
     * @return string Полный путь до папки последнего пакета
     */
    public static function getLastPackageFolder($dirToSearch)
    {
        $packageDirs = array();
        $lastPackageFolder = $dirToSearch . DIRECTORY_SEPARATOR . '1' . DIRECTORY_SEPARATOR;
        $dir = new \DirectoryIterator($dirToSearch);
        foreach ($dir as $item) {
            if ($item->isDot()) {
                continue;
            }
            if ($item->isDir()) {
                $lastPackageFolder = $item->getPathname();
                $dirName = (int) $item->getFilename();
                $packageDirs[$dirName] = $lastPackageFolder;
            }
        }
        if ($packageDirs) {
            ksort($packageDirs);
            $lastPackageFolder = end($packageDirs);
        }
        return $lastPackageFolder;
    }


    /**
     * Рекурсивное копирование директории и всего содержимого
     *
     * @param string $src Полный путь до директории копирование которой производится
     * @param string $dst Полный путь до директории в которую производится копирование
     */
    public static function recurseCopy($src, $dst)
    {
        if (!is_dir($dst) && !mkdir($dst) && !is_dir($dst)) {
            throw new \RuntimeException(sprintf('Папка "%s" не создана', $dst));
        }

        $dir = new \DirectoryIterator($src);
        foreach ($dir as $item) {
            if ($item->isDot()) {
                continue;
            }
            $path = $item->getPathname();
            $filename = $item->getFilename();
            if ($item->isDir()) {
                self::recurseCopy($path, $dst . DIRECTORY_SEPARATOR . $filename);
            } else {
                copy($path, $dst . DIRECTORY_SEPARATOR . $filename);
            }
        }
    }

    /**
     * Переносит файл в папку назначения с учётом распределения по пакетам
     *
     * @param string $filePath Полный путь до файла который необходимо переместить
     * @param string $directory Полный путь до директории в которой нужно разместить файл
     */
    public static function moveFile($filePath, $directory)
    {
        $filename = basename($filePath);
        $packageNumber = 0;
        $folder = '';
        $mainFolderFiles = array('import', 'offers', 'Documents', 'reports');

        // Если передаваемый файл не соответствует маске то скорее всего это картинка
        preg_match('/(\w*?)_/', $filename, $type);
        if (isset($type[1])) {
            // Если файл не может находиться в корневой директории, то сразу устанавливаем номер пакета
            /** @noinspection TypeUnsafeArraySearchInspection */
            if (in_array($type[1], $mainFolderFiles) === false) {
                $packageNumber++;
                $folder = $packageNumber . DIRECTORY_SEPARATOR;
            }

            // Ищем правильную директорию пакета для размещения файла
            $pathMask = $directory . $folder . $type[1] . '*.xml';
            $filesGlob = glob($pathMask);
            while ($filesGlob) {
                $packageNumber++;
                $folder = $packageNumber . DIRECTORY_SEPARATOR;
                $pathMask = $directory . $folder . $type[1] . '*.xml';
                $filesGlob = glob($pathMask);
            }
            $packageFolder = $directory . $folder;
            self::createFolder($packageFolder);

            $fulFilePath = $packageFolder . $filename;
            rename($filePath, $fulFilePath);
        }
    }

    /**
     * Проверяет существование файла в папке.
     * Если встречается архив то он будет распакован.
     * @param string $dirToScan Полный путь до папки в которой нужно искать файл
     * @param string $filename Имя файла, которое нужно искать в директории
     * @return bool Признак наличия файла
     */
    public static function checkFileExist($dirToScan, $filename)
    {
        $exist = false;
        $dir = new \DirectoryIterator($dirToScan);
        foreach ($dir as $item) {
            if ($item->isDot()) {
                continue;
            }
            $fileExtension = $item->getExtension();
            if ($fileExtension == 'zip') {
                $pathFile = $item->getPathname();
                $zip = new \PclZip($pathFile);
                $fileList = $zip->listContent();

                if (!$fileList) {
                    $errorInfo = $zip->errorInfo(true);
                    $path = $item->getRealPath();
                    unlink($path);
                    throw new \RuntimeException('Ошибка распаковки архива ' . $path . ' 111: ' . $errorInfo);
                }

                $file = $fileList[0];
                if (!($file['status'] == 'ok' && $file['size'] > 0)) {
                    $errorInfo = $zip->errorInfo(true);
                    throw new \RuntimeException('Ошибка распаковки архива 2: ' . $errorInfo);
                }

                $result = $zip->extract(PCLZIP_OPT_PATH, $dirToScan);
                if ($result == 0) {
                    $errorInfo = $zip->errorInfo(true);
                    throw new \RuntimeException('Ошибка распаковки архива 3: ' . $errorInfo);
                }

                unlink($pathFile);  // удаляем загруженный файл

                // Перезапускаем процесс поиска файла
                $exist = self::checkFileExist($dirToScan, $filename);
                break;
            }
            if ($item->getFilename() == $filename) {
                $exist = true;
            }
        }
        return $exist;
    }

    /**
     * Проверка "полноты" выгрузки по данным из файла
     *
     * @param string $filePath путь до одного из основных файлов выгрузки
     * @return bool флаг "полноты" выгрузки
     */
    public static function checkUpdateInfo($filePath)
    {
        $xml = new Xml($filePath);
        $xmlCategory = new XmlCategory($xml);

        if ($xmlCategory->validate()) {
            return $xmlCategory->updateInfo();
        }

        return true;
    }

    /**
     * Создание временных таблиц всех сущностей, участвующих в выгрузке
     */
    public static function prepareTables()
    {
        $dbCategory = new DbCategory();
        $dbCategory->prepareTable();

        $dbGood = new DbGood();
        $dbGood->prepareTable();

        $dbMedium = new DbMedium();
        $dbMedium->prepareTable();

        $dbOffer = new DbOffer();
        $dbOffer->prepareTable();

        $dbDirectory = new DbDirectory();
        $dbDirectory->prepareTable();

        $dbOrder = new DbOrder();
        $dbOrder->prepareTable();

        $dbUnit = new DbUnit();
        $dbUnit->prepareTable();

        $dbStorage = new DbStorage();
        $dbStorage->prepareTable();

        $custom = new DbCustom();
        $custom->prepareTables();
    }

    /**
     * Заключительный этап выгрузки - если в процессе импорта не было ошибок
     * меняем название временных таблиц на оригинальные
     */
    public static function renameTables()
    {
        $dbCategory = new DbCategory();
        $dbCategory->updateOrigTable();
        $dbCategory->dropTestTable();

        $dbGood = new DbGood();
        $dbGood->updateOrigTable();
        $dbGood->dropTestTable();

        $dbDirectory = new DbDirectory();
        $dbDirectory->updateOrigTable();
        $dbDirectory->dropTestTable();

        $dbMedium = new DbMedium();
        $dbMedium->updateOrigTable();
        $dbMedium->dropTestTable();

        $dbOffers = new DbOffer();
        $dbOffers->updateOrigTable();
        $dbOffers->dropTestTable();

        $dbOrder = new DbOrder();
        $dbOrder->updateOrigTable();
        $dbOrder->dropTestTable();

        $dbUnit = new DbUnit();
        $dbUnit->updateOrigTable();
        $dbUnit->dropTestTable();

        $dbStorage = new DbStorage();
        $dbStorage->updateOrigTable();
        $dbStorage->dropTestTable();

        $custom = new DbCustom();
        $custom->renameTables();
    }

    /**
     * Формирует список xml файлов предоставленных для обработки в указанной директории
     *
     * @param string $dirToScan Полный путь до директории которую необходимо просканировать
     * @return array Список xml файлов предоставленных для обработки
     */
    public static function getAllExchangeFiles($dirToScan)
    {
        $dirToScan = stream_resolve_include_path($dirToScan);
        $exchangeFiles = [];
        $dir = new \RecursiveDirectoryIterator($dirToScan);
        $iterator = new \RecursiveIteratorIterator($dir);
        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $fileExtension = $item->getExtension();
                if ($fileExtension === 'xml') {
                    $pathFile = $item->getPathname();
                    $fileName = $item->getFilename();
                    $exchangeFiles[$pathFile] = $fileName;
                }
            }
        }

        $config = require __DIR__ . '/load1cV3Settings.php'; // todo обнаружение в папке Mod.s
        $modelFactory = (new ModelAbstractFactory())->setConfig($config);

        uksort($exchangeFiles, function ($curr, $next) use ($exchangeFiles, $modelFactory) {
            $currName = basename($curr);
            $currSort = $modelFactory->createByFilename($currName)->getSort();
            $nextName = basename($next);
            $nextSort = $modelFactory->createByFilename($nextName)->getSort();

            if ($currSort !== $nextSort) {
                return $currSort - $nextSort;
            }

            $c = explode('_', $currName);
            $n = explode('_', $nextName);

            return  ($c[1] * 1000 + $c[2]) - ($n[1] * 1000 + $n[2]);
//                  'import' => 1,
//                  'offers' => 2,
//                  'prices' => 3,
//                  'pricesold' => 4,
//                  'rests' => 5,
//                  'imagesFile' => 6,
//                  'tegi' => 7,
//                  'nomenclProSov' => 8,
//                  'documents' => 9,
//                  'oplata' => 10,
//                  'addParameters' => 11,
        });

        return $exchangeFiles;
    }

    /**
     * Формирует список директорий в которых хранятся картинки
     *
     * @param string $dirToScan Полный путь до директории которую неободимо просканировать
     * @param string $dirToSearch Название директории в которой хранятся картинки
     * @return array Список директорий содержащих картинки для обраобтки
     */
    public static function getAllImagesFolder($dirToScan, $dirToSearch)
    {
        $exchangeFiles = array();
        $dir = new \DirectoryIterator($dirToScan);
        foreach ($dir as $item) {
            if ($item->isDot()) {
                continue;
            }
            if ($item->isDir()) {
                $pathFile = $item->getPathname() . DIRECTORY_SEPARATOR;
                if (strpos($pathFile, $dirToSearch) === false) {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $exchangeFiles = array_merge($exchangeFiles, self::getAllImagesFolder($pathFile, $dirToSearch));
                } else {
                    $exchangeFiles[$pathFile] = $pathFile;
                }
            }
        }
        ksort($exchangeFiles);
        return $exchangeFiles;
    }

    /**
     * Применение новых данных взятых из файлов обновлений
     */
    public static function finalUpdates()
    {
        $dbGood = new DbGood();
        $dbGood->updateGood();
        $dbCategory = new DbCategory();
        $dbCategory->updateGoodsCount();
        self::renameTables();
    }

    /**
     * Формирует человекопонятное отображение размера файла
     *
     * @param int $size Количество байт
     * @param int $precision Количество знаков в десятичной части
     * @return string Человекопонятное отображение размера файла
     */
    public static function humanFilesize($size, $precision = 2)
    {
        $mark = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {
        }
        return round($size, $precision).$mark[$i];
    }

    /**
     * Проверяет начался ли новый сеанс обмена данными с 1С?
     *
     * @param string $filePath Путь до файла выгрузки запрошенного на обработку
     * @param string $tmpResultFile Путь до временного файла
     *
     * @return bool Флаг обозначающий начало нового сеанса обмена с 1С
     */
    public static function checkExchangeStart($filePath, $tmpResultFile)
    {
        // Если нет временных таблиц, то начинается новый сеанс выгрузки
        $db = Db::getInstance();
        $result = $db->query('SHOW TABLES LIKE \'%_test\';');
        $res = $result->fetch_all(MYSQLI_ASSOC);
        if (count($res) === 0) {
            return true;
        }

        // Если обрабатываемый файл является файлом с разделами каталога, то начинается новый сеанс выгрузки
        $xml = new Xml($filePath);
        $xmlCategory = new XmlCategory($xml);
        if ($xmlCategory->validate()) {
            return true;
        }

        // Если обрабатываемый файл является файлом с заказами, то проверяем последнее обновление временного файла
        $xml = new Xml($filePath);
        $xmlOrder = new XmlOrder($xml);

        // Если файл оплат будет в корне, то нужна будет следующая проверка:
        $oplata = mb_strpos($filePath, 'Oplata') !== false;

        // Если временный файл последний раз обновлялся более 30 секунд назад, то начинается новый сеанс выгрузки
        return ($xmlOrder->validate() || $oplata) && time() - filemtime($tmpResultFile) > 30;
    }
}
