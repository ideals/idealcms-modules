<?php
namespace Shop\Structure\Service\Load1c_v2;

use Ideal\Structure\User\Model;
use Ideal\Core\Request;

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

    // импорт файлов из 1с
    public function import()
    {
        $user = new Model();
        $request = new Request();

        $this->directory = DOCUMENT_ROOT . '/tmp/1c/';

        if (!file_exists($this->directory)) {
            mkdir($this->directory, 0750, true);
        }

        $answer = array();

        switch ($request->mode) {
            case 'checkauth':
                if (!$user->login($request->PHP_AUTH_USER, $request->PHP_AUTH_PW)) {
                    $answer['checkauth'] = false;
                    return $answer;
                }
                return $answer['checkauth'] = true;

            case 'init':
                $tmp_files = glob($this->directory . '*.*');
                if (is_array($tmp_files)) {
                    foreach ($tmp_files as $v) {
                        unlink($v);
                    }
                }
                return $answer['init'] = true;

            case 'file':
                $filename = basename($request->filename);
                
                if ($filename == 'import.xml' || $filename == 'offers.xml') {
                    $dir = '';
                } else {
                    $dir = str_replace('/' . $filename, '', $_GET['filename']);
                }

                if (!file_exists($this->directory . '' . $dir)) {
                    mkdir($this->directory . '' . $dir, 0755, true);
                }

                $f = fopen($this->directory . '' . $dir . '/' . $filename, 'ab');
                fwrite($f, file_get_contents('php://input'));
                fclose($f);
                print "success\n";
                if ($filename == 'import.xml' OR $filename == 'offers.xml') {
                    return 0;
                }
                if ($this->config['manual'] == 1) return 0;
                return $this->tmpDir . '' . $dir . '/' . $filename;
                break;

            case 'import':
                $this->files = $this->readDir($this->directory);
                return $answer['improt'] = true;

            default:
                return false;
        }
    }

    protected function readDir($path)
    {
        $handle = opendir($path);
        $files = array();

        while (false !== ($entry = readdir($handle))) {
            if (0 === strpos($entry, '.')) {
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
        $answer = $newCategory->answer();

        echo 'category: ';
        print_r($answer);
        echo '<br/>';
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

        // Сохраняем результаты
        $dbGood->save($goods);

        // Уведомление пользователя о количестве добавленных, обновленны и удаленных товаров
        $answer = $newGood->answer();

        echo 'good: ';
        print_r($answer);
        echo '<br/>';
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
        $answer = $newDirectory->answer();

        echo 'directory: ';
        print_r($answer);
        echo '<br/>';
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


        unset ($xml, $xmlOffers, $newOffers);

        $xml = new Xml($this->files['1']['prices']);

        // инициализируем модель категорий в XML - XmlCategory
        $xmlPrices = new Offer\XmlOffer($xml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newOffers = new Offer\NewOffer($dbOffers, $xmlPrices);

        // Устанавливаем связь БД и XML
        $offers2 = $newOffers->parsePrice();


        unset ($xml, $xmlPrices, $newOffers);

        $xml = new Xml($this->files['1']['rests']);

        // инициализируем модель категорий в XML - XmlCategory
        $xmlRests = new Offer\XmlOffer($xml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newOffers = new Offer\NewOffer($dbOffers, $xmlRests);

        // Устанавливаем связь БД и XML
        $offers3 = $newOffers->parseRests();


        $offers = array_replace_recursive($offers1, $offers2, $offers3);
        // Сохраняем результаты
        $dbOffers->save($offers);

        $answer = $newOffers->answer();

        echo 'offer: ';
        print_r($answer);
        echo '<br/>';
    }
}
