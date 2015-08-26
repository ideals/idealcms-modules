<?php
use Shop\Structure\Service\Load1cV2;
use Ideal\Core;

ini_set('display_errors', 'Off');

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
$params = require 'config.php';

$config = Core\Config::getInstance();
$config->cms = array_merge($config->cms, array('errorLog'=>'email'));

// Каталог, в котором находятся модифицированные скрипты CMS
$config->cmsFolder = trim($subFolder . '/' . $cmsFolder, '/');

// Загружаем список структур из конфигурационных файлов структур
$config->loadSettings();

// сообщения об ошибках добавления insert, например, возвращает false
$fc = new Load1cV2\FrontController();
$fc->exportDebug(array('SERVER'=>$_SERVER,'REQUEST'=>$_REQUEST, 'time'=>date('h:i:s A')));
ob_clean();
ob_start();
$fc->import($params['info'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
$a = ob_get_contents();
$fc->exportDebug(array('$a'=>$a));
print $a;
