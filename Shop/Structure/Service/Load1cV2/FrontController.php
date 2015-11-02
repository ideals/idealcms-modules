<?php
namespace Shop\Structure\Service\Load1cV2;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Structure\User\Model;
use Ideal\Core\Request;
use Mail\Sender;
use Shop\Structure\Service\Load1cV2\Category\DbCategory;
use Shop\Structure\Service\Load1cV2\Category\XmlCategory;
use Shop\Structure\Service\Load1cV2\Directory\DbDirectory;
use Shop\Structure\Service\Load1cV2\Good\DbGood;
use Shop\Structure\Service\Load1cV2\Medium\DbMedium;
use Shop\Structure\Service\Load1cV2\Offer\DbOffer;
use Shop\Structure\Service\Load1cV2\Log\Log;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 30.06.2015
 * Time: 18:49
 */

class FrontController
{
    /** @var string абсолютный путь к папке для выгрузки */
    protected $directory;

    /** @var array массив, содержащий абсолютные пути и названия файлов выгрузки */
    protected $files;

    /** @var int максимальный размер получаемых архивов от 1с. Если файл больше - высылается в несколько частей */
    protected $filesize = 40960000;

    /** @var string ипсользовать ли сжатие yes|no */
    protected $useZip = 'yes';

    /** @var int количество полученных файлов от 1с. Необходимо для определения окончания выгрузки 1с */
    protected $countFiles = 0;
    protected $goods = array();

    /** @var \Shop\Structure\Service\Load1cV2\Log\Log Класс для логирования процесса обмена данными с 1С */
    protected $logClass = null;

    /** @var array Настройки для обмена данными с 1С */
    protected $config = array();

    public function __construct($config)
    {
        $this->config = $config;
        $this->logClass = new Log();
        $this->logClass->appendToLogMessage('Дата/время: ' . date('d.m.Y/H:i:s', time()) . "\n");
        $this->logClass->appendToLogMessage('Запрос: ' . $_SERVER['QUERY_STRING'] . "\n");
        $this->logClass->appendToLogMessage('POST-данные: ' . http_build_query($_POST) . "\n");

        // Объявляем функции которые будет отлавливать ошибки и заносить их в лог
        if (isset($this->config['keep_log']) && $this->config['keep_log'] == 'yes') {
            set_error_handler(array($this->logClass, 'logErrorHandler'));
            register_shutdown_function(array($this->logClass, 'logShutdownFunction'));
        }
    }

    public function __destruct()
    {
        if (isset($this->config['keep_log']) && $this->config['keep_log'] == 'yes') {
            $this->logClass->addToLog();
            $this->logClass->setLogMessage('');
        }
    }

    /**
     * Установка директории и получение списка файлов и путями
     *
     */
    public function loadFiles()
    {
        $this->directory = DOCUMENT_ROOT . $this->config['directory'];
        $this->files = $this->readDir($this->directory);
    }

    /**
     * API часть, вызываемая 1с базой.
     * в GET параметрах от 1с приходят следующие mode:
     * checkauth    - запрос авторизации
     * init         - конфигурирование выгрузки (использование сжатия, размер файла)
     * file         - передача файла
     * import       - запрос на импорт файла (в виду обеспечения целостности выгрузки импорт производится после
     *                  получения всех файлов от 1с)
     * В случае успешного выполнения запроса 1с ждет ответ success.
     *
     * @return int ВОЙД
     */
    public function import()
    {
        $user = new Model();
        $request = new Request();

        $this->filesize = intval($this->config['filesize']) * 1024 * 1024;
        if (isset($this->config['enable_zip'])) {
            $this->useZip = $this->config['enable_zip'];
        }

        $this->directory = DOCUMENT_ROOT . $this->config['directory'];

        // создание директории для выгрузки первого пакета
        if (!file_exists($this->directory . '1/')) {
            mkdir($this->directory . '1/', 0750, true);
        }

        // удаление файлов от предыдущей выгрузки
        if (time() - filemtime($this->directory) > 1800) {
            $this->purge();
        }

        // если запрос не на авторизацию и пользователь не залогинен - мрём
        if ($request->mode != 'checkauth' && !$user->checkLogin()) {
            print "failure\n";
            print "Ошибка: Вы не авторизованы";
            $this->logClass->appendToLogMessage('FAILURE: Попытка совершить действие не авторизовавшись.' . "\n");
            die();
        }

        switch ($request->mode) {
            case 'checkauth':
                if ($user->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
                    print "success\n";
                    print session_name() . "\n";
                    print session_id() . "\n";
                    $this->logClass->appendToLogMessage('Авторизация прошла успешно.' . "\n");
                } else {
                    print "failure\n";
                    print "Ошибка: пользователь не авторизован\n";
                    $this->logClass->appendToLogMessage("FAILURE: Ошибка авторизации.\n");
                    $this->logClass->appendToLogMessage("Пользователь: {$_SERVER['PHP_AUTH_USER']}.\n");
                    $this->logClass->appendToLogMessage("Пароль: {$_SERVER['PHP_AUTH_PW']}.\n");
                }
                return 0;

            case 'init':
                print "zip={$this->useZip}\n";
                print "file_limit={$this->filesize}\n";
                //TODO выяснить для чего нужна третья строка
                print "kakoeto = znachenie\n";
                // 1С ищет версию схемы в четвёртой строке при обмене заказами
                print "schema_version = 2.08\n";
                $this->logClass->appendToLogMessage("Установлены параметры для обмена данными.\n");
                $this->logClass->appendToLogMessage("Использовать архивирование - {$this->useZip}\n");
                $fileSize = self::humanFilesize($this->filesize);
                $this->logClass->appendToLogMessage("Ограничение размера принимаемого файла - {$fileSize}\n");
                return 0;

            case 'file':
                $filename = basename($request->filename);
                $this->logClass->appendToLogMessage("Получен файл \"{$filename}\". ");
                $dirName = str_replace($filename, '', $request->filename);

                if (strpos($filename, '.zip') !== false) {
                    $this->unzip($filename);
                    $this->logClass->appendToLogMessage("Архив успешно обработан.\n");
                } else {
                    $folder = 1;
                    $exists = array('prices', 'rests');
                    $handle = opendir($this->directory);

                    while (false !== ($entry = readdir($handle))) {
                        if (0 === strpos($entry, '.') || is_dir($this->directory . $entry)) {
                            continue;
                        }

                        preg_match('/(\w*?)_/', $entry, $type);
                        $exists[] = $type[1];
                    }

                    $type = array();

                    preg_match('/(\w*?)_/', $filename, $type);

                    // Если принимаем картинку, то определяем папку пакета, в котором её разместить
                    if (strpos($dirName, 'import_files') !== false) {
                        while (file_exists($this->directory . $folder . '/')) {
                            $folder++;
                        }
                        $folder--;
                        $filename = $dirName . $filename;
                    }

                    if (in_array($type[1], $exists)) {

                        $filesGlob = $this->checkFileExistInPackage($folder, $type[1]);
                        // Если находим то создаём новую директорию для нового пакета
                        while ($filesGlob !== false && is_array($filesGlob) && count($filesGlob) > 0) {
                            $folder++;
                            $filesGlob = $this->checkFileExistInPackage($folder, $type[1]);
                        }

                        // создание директории для выгрузки очередного пакета
                        if (!file_exists($this->directory . $folder . '/')) {
                            mkdir($this->directory . $folder . '/', 0750, true);
                        }

                        if (!file_exists($this->directory . $folder . '/' . $filename)) {
                            $f = fopen($this->directory . $folder . '/' . $filename, 'ab');
                            fwrite($f, file_get_contents('php://input'));
                            fclose($f);
                            $fileSize = self::humanFilesize(filesize($this->directory . $folder . '/' . $filename));
                            $this->logClass->appendToLogMessage("Размер файла - {$fileSize}.\n");
                            $this->logClass->appendToLogMessage("Помещён в директорию - \"{$this->directory}{$folder}/\".\n");
                        }
                    } else {
                        if (false !== strpos($filename, '.jpeg') || false !== strpos($filename, '.jpg') || false !== strpos($filename, '.gif')) {
                            // создание директории для выгрузки изображений
                            $filename = basename($request->filename);
                            $dirName = str_replace($filename, '', $request->filename);
                            if (!file_exists($this->directory . $folder . '/' . $dirName . '/' . $filename)) {
                                mkdir($this->directory . $folder . '/' . $dirName . '/', 0750, true);
                                $f = fopen($this->directory . $folder . '/' . $dirName . '/' . $filename, 'ab');
                                fwrite($f, file_get_contents('php://input'));
                                fclose($f);
                                $fileSize = self::humanFilesize(filesize($this->directory . $folder . '/' . $dirName . '/' . $filename));
                                $this->logClass->appendToLogMessage("Размер файла - {$fileSize}.\n");
                                $this->logClass->appendToLogMessage("Помещён в директорию - \"{$this->directory}{$folder}/{$dirName}\".\n");
                            }
                        } else {
                            $f = fopen($this->directory . $filename, 'ab');
                            fwrite($f, file_get_contents('php://input'));
                            fclose($f);
                            $fileSize = self::humanFilesize(filesize($this->directory . $filename));
                            $this->logClass->appendToLogMessage("Размер файла - {$fileSize}.\n");
                            $this->logClass->appendToLogMessage("Помещён в директорию - \"{$this->directory}\".\n");
                        }
                    }
                }

                print "success\n";
                return 0;

            case 'import':
                $filename = basename($request->filename);
                print "success\n";
                $this->logClass->appendToLogMessage("Файл \"{$filename}\" обработан.\n");
                break;

            case 'query':
                $xml = $this->generateExportXml();
                header("Content-type: text/xml; charset=windows-1251");
                print $xml;
                $this->logClass->appendToLogMessage("Файл заказов с сайта сформирован успешно.\n");
                die();

            case 'deactivate':
                $timeStart = $_SERVER['REQUEST_TIME'];
                $result = array();
                $this->loadFiles($this->config['directory']);
                $this->prepareTables();
                $result[] = $this->category();
                $result[] = $this->directory();
                $countPackages = $this->getCountPackages();
                for ($package = 1; $package <= $countPackages; $package++) {
                    $result[] = $this->good($package);
                    $result[] = $this->offer($package);
                }

                $vals = array();
                // подготовка для сообщения на почту
                foreach ($result as $response) {
                    if (isset($response['offer']) || isset($response['prices']) || isset($response['rests'])) {
                        $vals[] = "Шаг: {$response['step']} - <br/>";
                        unset ($response['step']);
                        foreach ($response as $value) {
                            $vals[] = "Шаг: {$value['step']} - <br/>".
                                "Добавлено:{$value['add']}<br/>".
                                "Обновлено:{$value['update']}<br/>";
                        }
                    } else {
                        $vals[] = "Шаг: {$response['step']} - <br/>".
                            "Добавлено:{$response['add']}<br/>".
                            "Обновлено:{$response['update']}<br/>";
                    }
                }

                $str = implode('<br/>', $vals);
                $html = "Выгрузка 1с<br/>" . $str;

                $con = Config::getInstance();
                $sender = new Sender();
                $sender->setSubj('Выгрузка 1с, версия 2, на сайте ' . $_SERVER['SERVER_NAME']);
                $sender->setHtmlBody($html);
                $sender->sent($con->robotEmail, $con->cms['adminEmail']);

                if (empty($request->curl)) {
                    // переименовываем временные таблицы на оригинальное название
                    $this->renameTables();
                }

                for ($package = 1; $package <= $countPackages; $package++) {
                    $this->loadImages($package, $timeStart);
                }

                echo "success\n";
                $this->logClass->appendToLogMessage("Сеанс связи с 1С завершён.\n");
                $html = str_replace('<br>', "\n", $html);
                $this->logClass->appendToLogMessage("{$html}\n");
                return 0;

            default:
                print "success\n";
                break;
        }
        return 0;
    }

    /**
     * Получение списка файла в директории $path. Вызывается рекурсивно для обхода вложенных директорий
     *
     * @param string $path абсолютный путь к директории
     * @return array массив данных о файлах в директории array('название' => 'абсолютный путь')
     */
    protected function readDir($path)
    {
        $handle = opendir($path);
        $files = array();

        while ($handle && false !== ($entry = readdir($handle))) {
            if (0 === strpos($entry, '.')
                || false !== strpos($entry, '.jpeg')
                || false !== strpos($entry, '.jpg')
                || 'import_files' == $entry) {
                continue;
            }

            // выгрузка изображений производится в ту же директорию, что и xml, но её обходить не надо
            if ($entry != 'import_files' && is_dir($path . $entry)) {
                $files[$entry] = $this->readDir($path . $entry . '/');
                continue;
            }

            // название файлов выгрузки имеет вид import___хэш.xml
            preg_match('/(\w*?)_/', $entry, $type);
            $this->countFiles++;
            $files[$type[1]] = $path . $entry;
        }

        return $files;
    }

    /**
     * Обновление категорий из xml выгрузки
     *
     * @return array информация о проведенных изменениях - add => count, update => count
     */
    public function category()
    {
        $xml = new Xml($this->files['import']);

        // инициализируем модель категорий в XML - XmlCategory
        $xmlCategory = new Category\XmlCategory($xml);

        $xmlCategoryXml = $xmlCategory->getXml();

        if (!empty($xmlCategoryXml)) {

            // инициализируем модель категорий в БД - DbCategory
            $dbCategory = new Category\DbCategory();

            // Инициализируем модель обновления категорий в БД из XML - NewCategory
            $newCategory = new Category\NewCategory($dbCategory, $xmlCategory);

            // Устанавливаем связь БД и XML
            $categories = $newCategory->parse();

            // Записываем обновлённые категории в БД
            $dbCategory->save($categories);

            // создание категории товаров, у которых в выгрузке не присвоена категория
            $dbCategory->createDefaultCategory();

            // Уведомление пользователя о количестве добавленных, удалённых и обновлённых категорий
            $answer = $newCategory->answer();
        } else {
            $answer = array(
                'infoText' => 'Обработка категорий/групп товаров',
                'successText'   => 'Категории не представленны в выгрузке',
            );
        }

        return $answer;
    }

    /**
     * Обновление товаров из выгрузки
     *
     * @param int $folder Номер пакета/папки, из которой берём выгрузку
     * @return array информация о проведенных изменениях - add => count, update => count
     */
    public function good($folder = 1)
    {
        $xml = new Xml($this->files[$folder]['import']);

        // Инициализируем модель товаров в БД - DbGood
        $dbGood = new Good\DbGood();

        // Инициализируем модель товаров в XML - XmlGood
        $xmlGood = new Good\XmlGood($xml);

        // Инициализируем модель обновления товаров в БД из XML - NewGood
        $newGood = new Good\NewGood($dbGood, $xmlGood);

        // Устанавливаем связь БД и XML и производим сравнение данных
        $goods = $newGood->parse();

        // Получение данных о проведённых изменениях
        $answer = $newGood->answer();

        // Данные для medium_categorylist
        $groups = $xmlGood->groups;

        unset($xmlGood, $newGood);

        // Сохраняем результаты
        $dbGood->save($goods);

        // Обновление информации в medium_categorylist
        $medium = new DbMedium();
        $medium->updateCategoryList($groups);

        // Установка количества товаров в группах и родительских группах
        $dbCategory = new Category\DbCategory();
        $dbCategory->updateGoodsCount();

        // Уведомление пользователя о количестве добавленных, обновлённых и удалённых товаров
        return $answer;
    }

    /**
     * Обновление справочников из выгрузки
     *
     * @return array информация о проведенных изменениях - add => count, update => count
     */
    public function directory()
    {
        $xml = new Xml($this->files['offers']);

        // инициализируем модель справочников в БД - DbDirectory
        $dbDirectory = new Directory\DbDirectory();

        // инициализируем модель справочников в XML - XmlDirectory
        $xmlDirectory = new Directory\XmlDirectory($xml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newDirectory = new Directory\NewDirectory($dbDirectory, $xmlDirectory);

        // Устанавливаем связь БД и XML, производим сравнение
        $directories = $newDirectory->parse();

        // Сохраняем результаты
        $dbDirectory->save($directories);
        // Уведомление пользователя о количестве добавленных, обновленны и удаленных товаров
        return $newDirectory->answer();
    }

    /**
     * Обновление предложений из выгрузки
     *
     * @param int $folder Номер пакета/папки, из которой берём выгрузку
     * @return array информация о проведенных изменениях - add => count, update => count
     */
    public function offer($folder = 1)
    {
        // получение xml с данными о предложениях
        $xml = new Xml($this->files[$folder]['offers']);

        // инициализируем модель предложений в БД - DbOffer
        $dbOffers = new Offer\DbOffer();

        // инициализируем модель предложений в XML - XmlOffer
        $xmlOffers = new Offer\XmlOffer($xml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newOffers = new Offer\NewOffer($dbOffers, $xmlOffers);

        // Устанавливаем связь БД и XML
        $offers = $newOffers->parse();

        $answer['offer'] = $newOffers->answer();
        $answer['offer']['infoText'] = 'Обработка общей информации о предожениях';


        unset ($xml, $xmlOffers, $newOffers);

        // получение xml с данными о ценах
        $xml = new Xml($this->files[$folder]['prices']);

        // инициализируем модель категорий в XML - XmlCategory
        $xmlPrices = new Offer\XmlOffer($xml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newOffers = new Offer\NewOffer($dbOffers, $xmlPrices);

        // Устанавливаем связь БД и XML
        $prices = $newOffers->parsePrice();

        $answer['prices'] = $newOffers->answer();
        $answer['prices']['infoText'] = 'Обработка цен предожений';

        unset ($xml, $xmlPrices, $newOffers);

        // получение xml с данными об остатках
        $xml = new Xml($this->files[$folder]['rests']);

        // инициализируем модель категорий в XML - XmlCategory
        $xmlRests = new Offer\XmlOffer($xml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newOffers = new Offer\NewOffer($dbOffers, $xmlRests);

        // Устанавливаем связь БД и XML
        $rests = $newOffers->parseRests();

        $result = array_replace_recursive($offers, $prices, $rests);

        unset($offers, $prices, $rests);

        // обновление данных для товаров - установка из выгрузки (часть данных идёт в cp_offer, другая - в cp_good)
        $dbGood = new Good\DbGood();

        $goods = $dbGood->getGoods('ID, id_1c');

        $struct = explode('-', $dbGood->prevGood);

        foreach ($result as $k => $item) {
            if (isset($item['good_id']) && isset($goods[$item['good_id']])) {
                $itemStructure = $struct[1] . '-' . $goods[$item['good_id']]['ID'];
                $result[$k]['prev_structure'] = $itemStructure;
            }
        }
        // Сохраняем результаты
        $dbOffers->save($result);

        $answer['rests'] = $newOffers->answer();
        $answer['rests']['infoText'] = 'Обработка остатков предожений';

        // сохранение предыдущих изменений (rename tables)
        $dbGood->updateOrigTable();
        // создание новой временной таблицы
        $dbGood->prepareTable(true);
        // обновление записей
        $dbGood->updateGood();

        return $answer;
    }

    /**
     * Ресайз изображений, находящихся в директории с выгрузкой. Оригинальные файлы удаляются
     *
     * @param int $folder Номер пакета/папки, из которой берём выгрузку
     * @param int $timeStart Время начала работы метода
     *
     * @return array данные о количестве отредактированных изображений
     */
    public function loadImages($folder = 1, $timeStart = 0)
    {
        $maxExecutionTime = (ini_get('max_execution_time') == 0) ?
            ini_get('max_input_time') :
            ini_get('max_execution_time');

        if ($timeStart == 0) {
            $timeStart = time();
        }
        $endTime = $timeStart + (int) $maxExecutionTime;

        $answer = array(
            'successText' => 'Изменений: ',
            'count'       => 0,
            'repeat'      => false
        );
        $this->directory = DOCUMENT_ROOT . $this->config['directory'] . $folder . '/' . $this->config['images_directory'];

        if (!file_exists($this->directory)) {
            $answer['successText'] .= $answer['count'];
            return $answer;
        }
        $handle = opendir($this->directory);

        if (isset($this->config['resize']) && !empty($this->config['resize'])) {
            list($w, $h) = explode('x', $this->config['resize']);
        }

        while (false !== ($entry = readdir($handle))) {
            if (0 === strpos($entry, '.')) {
                continue;
            }

            if (is_dir($this->directory . $entry)) {
                $incHandle = opendir($this->directory . $entry);

                while (false !== ($img = readdir($incHandle))) {
                    if (0 === strpos($img, '.')) {
                        continue;
                    }
                    if ($this->stopResize($endTime)) {
                        $answer['repeat'] = true;
                        $answer['successText'] .= $answer['count'];
                        return $answer;
                    }

                    if (false !== strpos($img, '.jpeg') || false !== strpos($img, '.jpg') || false !== strpos($img, '.gif')) {
                        $path = $this->directory . $entry . '/' .$img;
                        if (isset($w) && isset($h) && !empty($w) && !empty($h)) {
                            new Image($path, $w, $h);
                        } else {
                            $image = basename($img);
                            $entryTmp = substr($image, 0, 2);

                            if (!file_exists(DOCUMENT_ROOT . "/images/1c/{$entryTmp}")) {
                                mkdir(DOCUMENT_ROOT . "/images/1c/{$entryTmp}", 0750, true);
                            }

                            copy($path, DOCUMENT_ROOT . "/images/1c/{$entryTmp}/{$img}");
                        }
                        $answer['count']++;
                        unlink($path);
                    }
                }

                closedir($incHandle);
                $iterator = new \FilesystemIterator($this->directory . $entry);
                $isDirEmpty = !$iterator->valid();
                if ($isDirEmpty) {
                    rmdir($this->directory . $entry);
                }
            } else {
                if ($this->stopResize($endTime)) {
                    $answer['repeat'] = true;
                    $answer['successText'] .= $answer['count'];
                    return $answer;
                }

                if (false !== strpos($entry, '.jpeg') || false !== strpos($entry, '.jpg') || false !== strpos($entry, '.gif')) {
                    $path = $this->directory . $entry;
                    if (isset($w) && isset($h) && !empty($w) && !empty($h)) {
                        new Image($path, $w, $h);
                    } else {
                        $image = basename($img);
                        $entryTmp = substr($image, 0, 2);

                        if (!file_exists(DOCUMENT_ROOT . "/images/1c/{$entryTmp}")) {
                            mkdir(DOCUMENT_ROOT . "/images/1c/{$entryTmp}", 0750, true);
                        }

                        copy($path, DOCUMENT_ROOT . "/images/1c/{$entryTmp}/{$img}");
                    }
                    $answer['count']++;
                    unlink($path);
                }
            }
        }

        $answer['successText'] .= $answer['count'];
        return $answer;
    }

    /**
     * Метод рекурсивного удаления файлов и директорий
     *
     * @param string|null $dir абсолютный путь к директории
     */
    protected function purge($dir = null)
    {
        $path = is_null($dir) ? $this->directory : $dir;

        if (is_dir($path)) {
            $path = realpath($path);
            if (substr($path, -1) != DIRECTORY_SEPARATOR) {
                $path = $path . DIRECTORY_SEPARATOR;
            }
            if ($dh = opendir($path)) {
                while (($file = readdir($dh)) !== false) {
                    if (strpos($file, '.') === 0) {
                        continue;
                    }
                    $this->purge($path . $file);
                }
                closedir($dh);
            }
            rmdir($path);
        } else {
            unlink($path);
        }
    }

    /**
     * Распаковка архива.
     * @param string $filename basename файла
     */
    public function unzip($filename)
    {
        $exists = array('prices', 'rests');
        $config = Config::getInstance();
        $tmp = DOCUMENT_ROOT . $config->cms['tmpFolder'];

        gc_collect_cycles();
        $pathFile = $tmp . $filename;
        $file = fopen($pathFile, 'ab');

        $handle = fopen('php://input', 'rb');
        // построковое считывание файла. file_get_content может привести к фаталу из-за mapping'a в RAM
        while (!feof($handle)) {
            fwrite($file, fgets($handle));
        }
        fclose($file);
        fclose($handle);

        // если включено zip и установлен max filesize - ждем, пока не скачается весь файл
        if ($this->filesize != 0 && $_SERVER['CONTENT_LENGTH'] == $this->filesize) {
            print "success";
            exit;
        }
        $fileSize = self::humanFilesize(filesize($pathFile));
        $this->logClass->appendToLogMessage("Размер файла - {$fileSize}.\n");
        $this->logClass->appendToLogMessage("Помещён в директорию - \"{$tmp}\".\n");

        $zip = new \PclZip($pathFile);
        $fileList = $zip->listContent();

        if ($fileList == 0) {
            unlink($pathFile);  // удаляем загруженный файл
            $errorInfo = $zip->errorInfo(true);
            print "failure\n";
            print "Ошибка распаковки архива 1: {$errorInfo}";
            $this->logClass->appendToLogMessage("Ошибка распаковки архива - {$errorInfo}.\n");
            die;
        }

        $file = $fileList[0];
        if (!($file['status'] == 'ok' && $file['size'] > 0)) {
            unlink($pathFile);  // удаляем загруженный файл
            $errorInfo = $zip->errorInfo(true);
            print "failure\n";
            print "Ошибка распаковки архива 2: {$errorInfo}";
            $this->logClass->appendToLogMessage("Ошибка распаковки архива - {$errorInfo}.\n");

            die;
        }

        $handle = opendir($this->directory);
        while (false !== ($entry = readdir($handle))) {
            if (0 === strpos($entry, '.') || is_dir($this->directory . $entry)) {
                continue;
            }

            // важно сохранять очередность полученных файлов. первыми приходят мелкие import и offer, их кладем в 1с
            preg_match('/(\w*?)_/', $entry, $type);
            $exists[] = $type[1];
        }

        $a = $zip->extract(PCLZIP_OPT_BY_INDEX, '0', PCLZIP_OPT_PATH, $tmp . '/');
        if ($a == 0) {
            unlink($pathFile);
            $errorInfo = $zip->errorInfo(true);
            print "failure\n";
            print "Ошибка распаковки архива 3:{$errorInfo}";
            $this->logClass->appendToLogMessage("Ошибка распаковки архива - {$errorInfo}.\n");
            die;
        }

        preg_match('/(\w*?)_/', $file['filename'], $type);

        if (in_array($type[1], $exists)) {
            if (!file_exists($this->directory . $file['filename'])) {
                rename($tmp .'/'. $file['filename'], $this->directory . '1/' . $file['filename']);
            } else {
                unlink($tmp .'/'. $file['filename']);
            }
        } else {
            rename($tmp .'/'. $file['filename'], $this->directory . $file['filename']);
        }

        if (count($fileList) > 1) {
            $zip->extract(
                PCLZIP_OPT_PATH,
                DOCUMENT_ROOT . $this->config['directory'] . $this->config['images_directory'],
                PCLZIP_OPT_BY_PREG,
                '/jpg|jpeg/',
                PCLZIP_OPT_REMOVE_ALL_PATH
            );
        }

        unlink($pathFile);
    }

    /**
     * Заключительный этап выгрузки - если в процессе импорта не было ошибок
     * меняем название временных таблиц на оригинальные
     */
    public function renameTables()
    {
        $dbCategory     = new Category\DbCategory();
        $dbGood         = new Good\DbGood();
        $dbDirectory    = new Directory\DbDirectory();
        $dbMedium       = new Medium\DbMedium();
        $dbOffers       = new Offer\DbOffer();

        $dbCategory->updateOrigTable();
        $dbGood->updateOrigTable();
        $dbDirectory->updateOrigTable();
        $dbMedium->updateOrigTable();
        $dbOffers->updateOrigTable();
    }

    private function stopResize($endTime)
    {
        if ($endTime - time() <= 2) {
            return true;
        }
        return false;
    }

    private function generateExportXml()
    {
        $fileTemplate = array_slice(explode('\\', __FILE__), 0, -1);
        array_push($fileTemplate, 'export.xml');
        $fileTemplate = implode('\\', $fileTemplate);

        $template = simplexml_load_file($fileTemplate);
        $template->addAttribute('ВерсияСхемы', '2.08');
        $template->addAttribute('ДатаФормирования', date('Y-m-d', time()));

        $db = Db::getInstance();
        $config = Config::getInstance();

        $i = $config->db['prefix'];
        $orderSql = "SELECT sho.id, sho.date_create, sho.name, sho.price, d.good_id_1c, d.offer_id_1c, d.count, d.sum,".
            " stg.currency, sto.name as full_name, stg.coefficient, sto.price as fe FROM {$i}shop_structure_order sho".
            " LEFT JOIN {$i}shop_structure_orderdetail d on d.order_id=sho.id".
            " LEFT JOIN {$i}catalogplus_structure_good stg on d.good_id_1c=stg.id_1c".
            " LEFT JOIN {$i}catalogplus_structure_offer sto on sto.offer_id=d.offer_id_1c".
            " where sho.goods_id<>'' AND sho.export=1 LIMIT 0,200";

        $orderList = $db->select($orderSql);
        if (count($orderList) === 0) {
            die(print "success\n");
        }
        $items = array();
        foreach ($orderList as $element) {
            if (isset($items[$element['id']])) {
                $items[$element['id']]['goods'][] = array_slice($element, 4);
            } else {
                $items[$element['id']] = array_slice($element, 0, 4);
                $items[$element['id']]['goods'][] = array_slice($element, 4);
            }
        }
        unset($orderList);
        $upd = array();
        foreach ($items as $k => $item) {
            $charid = strtolower(md5($item['id'] . $item['date_create']));
            $guid = substr($charid, 0, 8) . '-' . substr($charid, 8, 4) . '-' . substr($charid, 12, 4) . '-' .
                substr($charid, 16, 4) . '-' . substr($charid, 20, 12);

            $doc = $template->xpath("//КоммерческаяИнформация");
            /* @var $doc[0] \SimpleXMLElement */
            $doc = $doc[0]->addChild("Документ");

            $doc->addChild("Ид", $guid);
            $doc->addChild("Номер", $item['id']);
            $doc->addChild("Дата", date('Y-m-d', $item['date_create']));
            $doc->addChild("ХозОперация", "Заказ товара");
            $doc->addChild("Роль", "Продавец");
            $doc->addChild("Валюта", "руб");
            $doc->addChild("Курс", 1);
            $doc->addChild("Сумма", number_format(floatval($item['price']) / 100, 2, '.', ''));

            $agents = $doc->addChild('Контрагенты');
            $agent = $agents->addChild('Контрагент');
            $charid = strtolower(md5('Физ лицо'));
            $guid = substr($charid, 0, 8) . '-' . substr($charid, 8, 4) . '-' . substr($charid, 12, 4) . '-' .
                substr($charid, 16, 4) . '-' . substr($charid, 20, 12);
            $agent->addChild("Ид", $guid);
            $agent->addChild("Наименование", "Физ лицо");
            $agent->addChild("Роль", "Покупатель");
            $agent->addChild("ПолноеНаименование", "Физ лицо");

            $xmlGoods = $doc->addChild('Товары');
            foreach ($item['goods'] as $good) {
                $xmlGood = $xmlGoods->addChild('Товар');

                $xmlGood->addChild('Ид', $good['good_id_1c'] . '#' . $good['offer_id_1c']);
                $xmlGood->addChild('Наименование', $good['full_name']);
                $xmlGood->addChild('ЦенаЗаЕдиницу', (int) $good['fe']/100);
                $xmlGood->addChild('Количество', $good['count']);
                $xmlGood->addChild('Сумма', $good['sum']/100);
                $xmlGood->addChild('Коэффициент', $good['coefficient']);
                $props = $xmlGood->addChild('ЗначенияРеквизитов');
                $prop = $props->addChild('ЗначениеРеквизита');
                $prop->addChild("Наименование", "ВидНоменклатуры");
                $prop->addChild("Значение", "Товар");

                $prop = $props->addChild('ЗначениеРеквизита');
                $prop->addChild("Наименование", "ТипНоменклатуры");
                $prop->addChild("Значение", "Товар");

                if (!isset($currency) && isset($good['currency'])) {
                    $currency = $good['currency'];
                }
            }

            if (!isset($currency)) {
                $currency = "RUB";
            }
            $doc->addChild("Валюта", $currency);

            $upd[] = $item['id'];
        }

        foreach ($upd as $item) {
            $db->query("UPDATE {$i}shop_structure_order SET export=0 WHERE id={$item}");
        }
        return $template->asXML();
    }

    /**
     * Создание временных таблиц всех сущностей, участвующих в выгрузке
     */
    public function prepareTables()
    {
        $xml = new Xml($this->files['import']);
        $xmlCategory = new XmlCategory($xml);
        $isUpdate = $xmlCategory->updateInfo();

        $dbCategory = new DbCategory();
        $dbCategory->prepareTable($isUpdate);

        $dbGood = new DbGood();
        $dbGood->prepareTable($isUpdate);

        $dbMedium = new DbMedium();
        $dbMedium->prepareTable($isUpdate);

        $dbOffer = new DbOffer();
        $dbOffer->prepareTable($isUpdate);

        $dbDirectory = new DbDirectory();
        $dbDirectory->prepareTable($isUpdate);
    }

    public function getCountPackages()
    {
        $countPackages = 0;
        foreach ($this->files as $value) {
            if (is_array($value)) {
                $countPackages++;
            }
        }
        return $countPackages;
    }

    /**
     * Ищет в имеющихся директориях пакетов файлы по маске
     *
     * @param int $folder Номер пакета/папки
     * @param string $fileNamePart Часть имени файла используемая для построения маски
     * @return array Результат поиска файла
     */
    private function checkFileExistInPackage($folder, $fileNamePart)
    {
        $fileNameMask = $this->directory . $folder . '/' . $fileNamePart . '*.xml';
        return glob($fileNameMask);
    }

    /**
     * Формирует человекопонятное отображение размера файла
     *
     * @param int $size Количество байт
     * @param int $precision Количество знаков в десятичной части
     * @return string Человекопонятное отображение размера файла
     */
    private function humanFilesize($size, $precision = 2)
    {
        $mark = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) {
        }
        return round($size, $precision).$mark[$i];
    }
}
