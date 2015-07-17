<?php
namespace Shop\Structure\Service\Load1c_v2;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 30.06.2015
 * Time: 18:49
 */

class FrontController
{
    // категории
    public function run()
    {
        $xml = new Xml(DOCUMENT_ROOT . '/tmp/1c', 'import.xml');

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
    public function run2()
    {
        $importXml = new Xml(DOCUMENT_ROOT . '/tmp/1c', 'import.xml');

        // инициализируем модель товаров в БД - DbGood
        $dbGood = new Good\DbGood();

        // инициализируем модель категорий в XML - XmlCategory
        $xmlGood = new Good\XmlGood($importXml);

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

    // предложения
    public function run3()
    {
        $offersXml = new Xml(DOCUMENT_ROOT . '/tmp/1c', 'offers.xml');

        // инициализируем модель товаров в БД - DbGood
        $dbOffers = new Offer\DbOffer();

        // инициализируем модель категорий в XML - XmlCategory
        $xmlOffers = new Offer\XmlOffer($offersXml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newOffers = new Offer\NewOffer($dbOffers, $xmlOffers);

        // Устанавливаем связь БД и XML
        $offers = $newOffers->parse();

        // Сохраняем результаты
        $dbOffers->save($offers);

        // Уведомление пользователя о количестве добавленных, обновленны и удаленных товаров
        $answer = $newOffers->answer();

        echo 'offer: ';
        print_r($answer);
        echo '<br/>';
    }
}
