<?php
namespace Shop\Structure\Service\Load1cV2;

use Ideal\Core\Config;
use Ideal\Structure\User\Model;
use Ideal\Core\Request;
use Shop\Structure\Service\Load1cV2\Image;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 30.06.2015
 * Time: 18:49
 */

class FrontController
{
    protected $directory;

    protected $files;

    public function loadFiles($dir)
    {
        $this->directory = DOCUMENT_ROOT . $dir;
        $this->files = $this->readDir($this->directory);
    }

    // импорт файлов из 1с
    public function import($conf)
    {
        $user = new Model();
        $request = new Request();

        $this->directory = DOCUMENT_ROOT . $conf['directory'];

        if (!file_exists($this->directory . '1/')) {
            mkdir($this->directory . '1/', 0750, true);
        }

        if (time() - filemtime($this->directory) > 1800) {
            $this->purge();
        }

        if ($request->mode != 'checkauth' && !$user->checkLogin()) {
            print "Пользователь не авторизован";
            die();
        }

        switch ($request->mode) {
            case 'checkauth':
                if ($user->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
                    print "success\n";
                    print session_name() . "\n";
                    print session_id();
                } else {
                    print "Пользователь не авторизован\n";
                }
                return 0;

            case 'init':
                print "zip=yes\n";
                print "file_limit=0\n";
                return 0;

            case 'file':
                print "accept file\n";
                $filename = basename($request->filename);
                if (substr($filename, -1) == '&') {
                    $filename = substr($filename, 0, -1);
                }

                if (strpos($filename, '.zip') !== false) {
                    $this->unzip($filename);
                } else {
                    $path = $this->directory . '1/' . $filename;
                    $f = fopen($this->directory . '1/' . $filename, 'ab');
                    fwrite($f, file_get_contents('php://input'));
                    fclose($f);

                    if (isset($path) && getimagesize($path)) {
                        list($w, $h) = explode('x', $conf['resize']);
                        new Image($path, $w, $h);
                        unlink($path);
                    }
                }

                print "success\n";
                return 0;

            case 'import':
                $this->files = $this->readDir($this->directory);
                $file = basename($request->filename);
                if (isset($this->files['import']) && basename($this->files['import']) == $file) {
                    $this->category();
                } elseif (isset($this->files['1']['import']) && basename($this->files['1']['import']) == $file) {
                    $this->good();
                } elseif (isset($this->files['offers']) && basename($this->files['offers']) == $file) {
                    $this->directory();
                } elseif (
                    isset($this->files['1']['prices']) &&
                    isset($this->files['1']['rests']) &&
                    isset($this->files['1']['offers'])
                ) {
                    $this->offer();
                }
                print "success";
                return 0;

            default:
                return false;
        }
    }

    protected function readDir($path)
    {
        $handle = opendir($path);
        $files = array();

        while (false !== ($entry = readdir($handle))) {
            if (0 === strpos($entry, '.')
                || false !== strpos($entry, '.jpeg')
                || false !== strpos($entry, '.jpg')) {
                continue;
            }

            if (is_dir($path . $entry)) {
                $files[$entry] = $this->readDir($path . $entry . '/');
                continue;
            }

            preg_match('/(\w*?)_/', $entry, $type);
            $files[$type[1]] = $path . $entry;
        }

        return $files;
    }

    // категории
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

    // товары
    public function good()
    {
        $xml = new Xml($this->files['1']['import']);

        // инициализируем модель товаров в БД - DbGood
        $dbGood = new Good\DbGood();

        // инициализируем модель категорий в XML - XmlCategory
        $xmlGood = new Good\XmlGood($xml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newGood = new Good\NewGood($dbGood, $xmlGood);

        // Устанавливаем связь БД и XML
        $goods = $newGood->parse();

        $dbGood->truncateCategoryList();
        // Сохраняем результаты
        $dbGood->save($goods);

        $dbGood->updateCategoryList();

        // Уведомление пользователя о количестве добавленных, обновленны и удаленных товаров
        return $newGood->answer();
    }

    // справочники
    public function directory()
    {
        $xml = new Xml($this->files['offers']);

        // инициализируем модель товаров в БД - DbGood
        $dbDirectory = new Directory\DbDirectory();

        // инициализируем модель категорий в XML - XmlCategory
        $xmlDirectory = new Directory\XmlDirectory($xml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newDirectory = new Directory\NewDirectory($dbDirectory, $xmlDirectory);

        // Устанавливаем связь БД и XML
        $directories = $newDirectory->parse();

        // Сохраняем результаты
        $dbDirectory->save($directories);

        // Уведомление пользователя о количестве добавленных, обновленны и удаленных товаров
        return $newDirectory->answer();
    }

    // предложения
    public function offer()
    {
        $xml = new Xml($this->files['1']['offers']);

        // инициализируем модель товаров в БД - DbGood
        $dbOffers = new Offer\DbOffer();

        // инициализируем модель категорий в XML - XmlCategory
        $xmlOffers = new Offer\XmlOffer($xml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newOffers = new Offer\NewOffer($dbOffers, $xmlOffers);

        // Устанавливаем связь БД и XML
        $offers1 = $newOffers->parse();

        $answer['offer'] = $newOffers->answer();

        unset ($xml, $xmlOffers, $newOffers);

        $xml = new Xml($this->files['1']['prices']);

        // инициализируем модель категорий в XML - XmlCategory
        $xmlPrices = new Offer\XmlOffer($xml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newOffers = new Offer\NewOffer($dbOffers, $xmlPrices);

        // Устанавливаем связь БД и XML
        $offers2 = $newOffers->parsePrice();

        $answer['prices'] = $newOffers->answer();

        unset ($xml, $xmlPrices, $newOffers);

        $xml = new Xml($this->files['1']['rests']);

        // инициализируем модель категорий в XML - XmlCategory
        $xmlRests = new Offer\XmlOffer($xml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newOffers = new Offer\NewOffer($dbOffers, $xmlRests);

        // Устанавливаем связь БД и XML
        $offers3 = $newOffers->parseRests();


        $offers = array_replace_recursive($offers1, $offers2, $offers3);
        unset($offers1, $offers2, $offers3);
        // Сохраняем результаты
        $dbOffers->save($offers);

        $answer['rests'] = $newOffers->answer();

        $dbGood = new Good\DbGood();
        $dbGood->updateGood();

        return $answer;
    }

    public function loadImages($dir)
    {
        $count = 0;
        $this->directory = DOCUMENT_ROOT . $dir['directory'] . $dir['images_directory'];

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
                        $count++;
                        unlink($path);
                    }
                }

                rmdir($this->directory . $entry);
            }
        }

        return array(
            'step' => 'Ресайз изображений',
            'count'  => $count
        );
    }

    private function purge()
    {
        $tmp_files = glob($this->directory . '*.*');
        if (is_array($tmp_files)) {
            foreach ($tmp_files as $v) {
                unlink($v);
            }
        }
    }

    private function unzip($filename)
    {
        $exists = array('prices', 'rests');
        $config = Config::getInstance();
        $tmp = DOCUMENT_ROOT . $config->cms['tmpFolder'];

        $f = fopen($tmp . $filename, 'ab');
        $pathFile = $tmp . $filename;
        fwrite($f, file_get_contents('php://input'));
        fclose($f);

        $zip = new \ZipArchive;
        $res = $zip->open($pathFile);
        if ($res === true) {
            $unzipName = $zip->getNameIndex(0);
            $zip->extractTo($tmp);
            $zip->close();
        } else {
            $unzipName = '';
            print_r('не удалось открыть архив' . $pathFile);
            // todo не смог распаковать архив
        }
        unlink($pathFile);

        $handle = opendir($this->directory);
        while (false !== ($entry = readdir($handle))) {
            if (0 === strpos($entry, '.') || is_dir($this->directory . $entry)) {
                continue;
            }

            preg_match('/(\w*?)_/', $entry, $type);
            $exists[] = $type[1];
        }

        preg_match('/(\w*?)_/', $unzipName, $type);

        if (in_array($type[1], $exists)) {
            if (!file_exists($this->directory . $unzipName)) {
                rename($tmp .'/'. $unzipName, $this->directory . '1/' . $unzipName);
            } else {
                unlink($tmp .'/'. $unzipName);
            }
        } else {
            rename($tmp .'/'. $unzipName, $this->directory . $unzipName);
        }
    }
}
