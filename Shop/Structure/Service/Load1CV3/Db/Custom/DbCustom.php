<?php

namespace Shop\Structure\Service\Load1CV3\Db\Custom;

use Shop\Structure\Service\Load1CV3\Db\AbstractDb;

class DbCustom extends AbstractDb
{
    /**
     * @noinspection MagicMethodsValidityInspection
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct()
    {
    }

    /**
     * Подготовка таблиц перед началом выгрузки
     *
     * Нужно создать временную таблицу, на основе существующей и скопировать туда данные из основной таблицы
     */
    public function prepareTable(): void
    {
    }

    /**
     * Удаление старой таблицы и переименование временной в основную
     */
    public function renameTable(): void
    {
    }
}
