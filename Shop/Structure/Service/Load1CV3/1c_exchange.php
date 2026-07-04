<?php

namespace Shop\Structure\Service\Load1CV3;

use Ideal\Core\Config;
use Ideal\Core\Util;

$isConsole = true;
require_once $_SERVER['DOCUMENT_ROOT'] . '/_.php';

$config = Config::getInstance();

$cmsFolderPath = DOCUMENT_ROOT . DIRECTORY_SEPARATOR . $config->cmsFolder . DIRECTORY_SEPARATOR;
$settingsFilePath = $cmsFolderPath . 'load1CV3Settings.php';

// Если нет файла в папке админки, то копируем его туда из папки модуля
if (!file_exists($settingsFilePath)) {
    $settingsFilePath = $cmsFolderPath . 'Mods/Shop/Structure/Service/Load1CV3/load1CV3Settings.php';
    if (!file_exists($settingsFilePath)) {
        // Если файла настроек нет и в папке модуля, то выбрасываем исключение
        throw new \RuntimeException('Отсутствует файл настроек модуля выгрузки');
    }
}

$params = require $settingsFilePath;

/** @var array $cmsSettings Массив общих настроек cms*/
$cmsSettings = $config->cms;

$config->cms = array_merge($cmsSettings, ['errorLog' => 'email']);

try {
    $fc = new FrontController($params);
    if (ob_get_contents()) {
        ob_clean();
    }

    ob_start();
    $fc->run();
    $a = ob_get_clean();
    print $a;
} catch (\Throwable $throwable) {
    print "failure\n";
    print $throwable->getMessage();
}

$errors = Util::$errorArray;
if (count($errors) > 0) {
    print "Возникли следующие ошибки: \n";
    print_r($errors);
}
