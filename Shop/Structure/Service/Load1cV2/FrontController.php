<?php
namespace Shop\Structure\Service\Load1cV2;

use Ideal\Core\Config;
use Ideal\Structure\User\Model;
use Ideal\Core\Request;
use Mail\Sender;

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

    /** @var int количество полученных файлов от 1с. Необходимо для определения окончания выгрузки 1с */
    protected $countFiles = 0;

    /**
     * Установка директории и получение списка файлов и путями
     *
     * @param string $dir путь до папки с выгрузкой от docs папки.
     */
    public function loadFiles($dir)
    {
        $this->directory = DOCUMENT_ROOT . $dir;
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
     * @param array $conf данные из корневого конфигурационного файла
     * @return int ВОЙД
     */
    public function import($conf)
    {
        $user = new Model();
        $request = new Request();

        $this->filesize = intval($conf['filesize']) * 1024 * 1024;
        $this->directory = DOCUMENT_ROOT . $conf['directory'];

        // создание директории для выгрузки
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
            die();
        }

        switch ($request->mode) {
            case 'checkauth':
                if ($user->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
                    print "success\n";
                    print session_name() . "\n";
                    print session_id();
                } else {
                    print "failure\n";
                    print "Ошибка: пользователь не авторизован\n";
                }
                return 0;

            case 'init':
                print "zip=yes\n";
                print "file_limit={$this->filesize}\n";
                return 0;

            case 'file':
                $filename = basename($request->filename);

                if (strpos($filename, '.zip') !== false) {
                    $this->unzip($filename, $conf);
                } else {
                    $path = $this->directory . '1/' . $filename;
                    $f = fopen($this->directory . '1/' . $filename, 'ab');
                    fwrite($f, file_get_contents('php://input'));
                    fclose($f);

                    if (getimagesize($path)) {
                        list($w, $h) = explode('x', $conf['resize']);
                        new Image($path, $w, $h);
                        unlink($path);
                    }
                }

                print "success\n";
                return 0;

            case 'import':
                print "success";
                break;

            default:
                break;
        }

        $this->countFiles = 0;
        $this->files = $this->readDir($this->directory);
        // если пришли все файлы начинаем выгрузку
        if ($this->countFiles == 6) {
            $result = array();
            $result[] = $this->category();
            $result[] = $this->good();
            $result[] = $this->directory();
            $result[] = $this->offer();

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

            // переименовываем временные таблицы на оригинальное название
            $this->renameTables();

            // запускаем resizer
            $this->loadImages($conf['info']);
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

        while (false !== ($entry = readdir($handle))) {
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

        // инициализируем модель категорий в БД - DbCategory
        $dbCategory = new Category\DbCategory();

        // инициализируем модель категорий в XML - XmlCategory
        $xmlCategory = new Category\XmlCategory($xml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newCategory = new Category\NewCategory($dbCategory, $xmlCategory);

        // Устанавливаем связь БД и XML
        $categories = $newCategory->parse();

        // Записываем обновлённые категории в БД
        $dbCategory->save($categories);

        // создание категории товаров, у которых в выгрузке не присвоена категория
        $dbCategory->createDefaultCategory();

        // Уведомление пользователя о количестве добавленных, удалённых и обновлённых категорий
        return $newCategory->answer();
    }

    /**
     * Обновление товаров из выгрузки
     *
     * @return array информация о проведенных изменениях - add => count, update => count
     */
    public function good()
    {
        $xml = new Xml($this->files['1']['import']);

        // инициализируем модель товаров в БД - DbGood
        $dbGood = new Good\DbGood();

        // инициализируем модель категорий в XML - XmlCategory
        $xmlGood = new Good\XmlGood($xml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newGood = new Good\NewGood($dbGood, $xmlGood);

        // Устанавливаем связь БД и XML и производим сравнение данных
        $goods = $newGood->parse();

        // получение данных о проведённых изменениях
        $answer = $newGood->answer();

        // данные для medium_categorylist
        $groups = $xmlGood->groups;

        unset($xmlGood, $newGood);

        // удаление данных из medium_categorylist если необходимо (onlyUpdate = false)
        $dbGood->truncateCategoryList();

        // Сохраняем результаты
        $dbGood->save($goods);

        // обновление информации в medium_categorylist
        $dbGood->updateCategoryList($groups);

        // Уведомление пользователя о количестве добавленных, обновленны и удаленных товаров
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
     * @return array информация о проведенных изменениях - add => count, update => count
     */
    public function offer()
    {
        // получение xml с данными о предложениях
        $xml = new Xml($this->files['1']['offers']);

        // инициализируем модель предложений в БД - DbOffer
        $dbOffers = new Offer\DbOffer();

        // инициализируем модель предложений в XML - XmlOffer
        $xmlOffers = new Offer\XmlOffer($xml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newOffers = new Offer\NewOffer($dbOffers, $xmlOffers);

        // Устанавливаем связь БД и XML
        $offers = $newOffers->parse();

        $answer['offer'] = $newOffers->answer();

        unset ($xml, $xmlOffers, $newOffers);

        // получение xml с данными о ценах
        $xml = new Xml($this->files['1']['prices']);

        // инициализируем модель категорий в XML - XmlCategory
        $xmlPrices = new Offer\XmlOffer($xml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newOffers = new Offer\NewOffer($dbOffers, $xmlPrices);

        // Устанавливаем связь БД и XML
        $prices = $newOffers->parsePrice();

        $answer['prices'] = $newOffers->answer();

        unset ($xml, $xmlPrices, $newOffers);

        // получение xml с данными об остатках
        $xml = new Xml($this->files['1']['rests']);

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
            if (array_key_exists('good_id', $goods)) {
                $itemStructure = $struct[1] . '-' . $goods[$item['good_id']]['ID'];
                $result[$k]['prev_structure'] = $itemStructure;
            }
        }
        // Сохраняем результаты
        $dbOffers->save($result);

        $answer['rests'] = $newOffers->answer();

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
     * @param array $dir данные из конфигурационного файла
     * @return array данные о количестве отредактированных изображений
     */
    public function loadImages($dir)
    {
        $answer = array(
            'step'      => 'Ресайз изображений',
            'count'     => 0,
        );
        $this->directory = DOCUMENT_ROOT . $dir['directory'] . $dir['images_directory'];

        if (!file_exists($this->directory)) {
            return $answer;
        }
        $handle = opendir($this->directory);

        list($w, $h) = explode('x', $dir['resize']);

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
                    if (false !== strpos($img, '.jpeg') || false !== strpos($img, '.jpg')) {
                        $path = $this->directory . $entry . '/' .$img;
                        new Image($path, $w, $h);
                        $answer['count']++;
                        unlink($path);
                    }
                }

                closedir($incHandle);
                rmdir($this->directory . $entry);
            } else {
                if (false !== strpos($entry, '.jpeg') || false !== strpos($entry, '.jpg')) {
                    $path = $this->directory . $entry;
                    new Image($path, $w, $h);
                    $answer['count']++;
                    unlink($path);
                }
            }
        }

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
     * @param array $conf данные из конфигурационного файла
     */
    public function unzip($filename, $conf)
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

        $zip = new \PclZip($pathFile);
        $fileList = $zip->listContent();

        if ($fileList == 0) {
            unlink($pathFile);  // удаляем загруженный файл
            print "failure\n";
            print "Ошибка распаковки архива 1: ".$zip->errorInfo(true);
            die;
        }

        $file = $fileList[0];
        if (!($file['status'] == 'ok' && $file['size'] > 0)) {
            unlink($pathFile);  // удаляем загруженный файл
            print "failure\n";
            print "Ошибка распаковки архива 2: ".$zip->errorInfo(true);
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
            print "failure\n";
            print "Ошибка распаковки архива 3:".$zip->errorInfo(true);
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
                DOCUMENT_ROOT . $conf['directory'] . $conf['images_directory'],
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
        $dbOffers       = new Offer\DbOffer();

        $dbCategory->updateOrigTable();
        $dbGood->updateOrigTable();
        $dbDirectory->updateOrigTable();
        $dbOffers->updateOrigTable();
    }
}
