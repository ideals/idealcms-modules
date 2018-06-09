<?php
use Shop\Structure\Service\Load1cV2;
use Ideal\Core;

ini_set('display_errors', 'On');

$cmsFolder = 'don';
$subFolder = '';

// Абсолютный адрес корня сервера, не должен оканчиваться на слэш.
define('DOCUMENT_ROOT', getenv('SITE_ROOT') ? getenv('SITE_ROOT') : $_SERVER['DOCUMENT_ROOT']);

// В пути поиска по умолчанию включаем корень сайта, путь к Ideal и папке кастомизации CMS
set_include_path(
    get_include_path()
    . PATH_SEPARATOR . DOCUMENT_ROOT
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Ideal.c/'
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Ideal/'
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Mods.c/'
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Mods/'
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Ideal/Library/'
);

// Подключаем автозагрузчик классов
require_once 'Core/AutoLoader.php';
require_once 'Library/pclzip.lib.php';

$config = Core\Config::getInstance();

$cmsFolderPath = DOCUMENT_ROOT . DIRECTORY_SEPARATOR . $cmsFolder  . DIRECTORY_SEPARATOR;
$settingsFilePath = $cmsFolderPath . 'load1cV2Settings.php';

// Если нет файла в папке админки, то копируем его туда из папки модуля
if (!file_exists($settingsFilePath)) {
    $settingsFilePath = $cmsFolderPath . 'Mods/Shop/Structure/Service/Load1cV2/load1cV2Settings.php';
    if (!file_exists($settingsFilePath)) {
        // Если файла настроек нет и в папке модуля то выбрасываем исключение
        throw new \Exception('Отсутствует файл настроек модуля выгрузки');
    }
}

$params = require $settingsFilePath;

$config->cms = array_merge($config->cms, array('errorLog'=>'email'));

// Каталог, в котором находятся модифицированные скрипты CMS
$config->cmsFolder = trim($subFolder . '/' . $cmsFolder, '/');

// Загружаем список структур из конфигурационных файлов структур
$config->loadSettings();

// Класс логирования
$logClass = new Shop\Structure\Service\Load1cV2\Log\Log();
$fc = new Load1cV2\FrontController($params, $logClass);
if (ob_get_contents()) {
    ob_clean();
}
ob_start();
$fc->import();
$a = ob_get_clean();
print $a;
