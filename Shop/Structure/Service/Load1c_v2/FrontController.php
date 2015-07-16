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
    public function run()
    {
        /**
         * считываем категории из БД
         * считать категории из файла
         *
         * категории из базы и категории из файла - на генерацию запросов к БД в отдельный класс и интерфейс для
         * определения ид категории по ид-1с
         */

        // РАБОТА С КАТЕГОРИЯМИ
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

        print_r($answer);
    }

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

        print_r($answer);
    }
}
