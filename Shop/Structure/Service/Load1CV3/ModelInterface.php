<?php

namespace Shop\Structure\Service\Load1CV3;

interface ModelInterface
{
    /**
     * Инициализирует необходимые значения $answer['infoText'] и $sort
     *
     * @return void
     */
    public function init(): void;

    /**
     * Запуск процесса обработки файлов *.xml
     *
     * @param int $packageNum Номер пакета
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($packageNum): array;

    public function answer(): array;

    public function getSort(): int;
}