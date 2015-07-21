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
    public function category()
    {
        $xml = new Xml(DOCUMENT_ROOT . '/tmp/1c', 'import___6738d00c-9bcb-40c7-900d-a1a3dac5d350.xml');

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
        $xml = new Xml(DOCUMENT_ROOT . '/tmp/1c/1', 'import___93472611-a08d-4698-8772-730908511f5e.xml');

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
        $xml = new Xml(DOCUMENT_ROOT . '/tmp/1c', 'offers___ac2db4eb-e2be-4cf0-9196-0f2eacac296c.xml');

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
        $xml = new Xml(DOCUMENT_ROOT . '/tmp/1c/1', 'offers___b245f08d-d893-42a3-b599-5f562a1698f7.xml');

        // инициализируем модель товаров в БД - DbGood
        $dbOffers = new Offer\DbOffer();

        // инициализируем модель категорий в XML - XmlCategory
        $xmlOffers = new Offer\XmlOffer($xml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newOffers = new Offer\NewOffer($dbOffers, $xmlOffers);

        // Устанавливаем связь БД и XML
        $offers1 = $newOffers->parse();


        unset ($xml, $xmlOffers, $newOffers);

        $xml = new Xml(DOCUMENT_ROOT . '/tmp/1c/1', 'prices___17286cc7-8713-468e-b7a7-c545cd363b59.xml');

        // инициализируем модель категорий в XML - XmlCategory
        $xmlPrices = new Offer\XmlOffer($xml);

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newOffers = new Offer\NewOffer($dbOffers, $xmlPrices);

        // Устанавливаем связь БД и XML
        $offers2 = $newOffers->parsePrice();


        unset ($xml, $xmlPrices, $newOffers);

        $xml = new Xml(DOCUMENT_ROOT . '/tmp/1c/1', 'rests___58b2c59a-4eef-4ccc-9b59-bf9c82d1d24f.xml');

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
