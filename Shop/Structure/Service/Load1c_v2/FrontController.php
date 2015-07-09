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

        // инициализируем модель категорий в БД - DbCategory
        $dbCategory = new DbCategory();

        // инициализируем модель категорий в XML - XmlCategory
        $xmlCategory = new XmlCategory(DOCUMENT_ROOT . '/tmp/1c');

        // Инициализируем модель обновления категорий в БД из XML - NewCategory
        $newCategory = new NewCategory($dbCategory, $xmlCategory);

        // Устанавливаем связь БД и XML
        $categories = $newCategory->parse();

        // Записываем обновлённые категории в БД
        $dbCategory->save($categories);

        // Уведомление пользователя о количестве добавленных, удалённых и обновлённых категорий
        $answer = $newCategory->answer();
    }
}
