<?php
namespace Shop\Structure\Service\Load1c;

$cmsFolder = 'super';
$subFolder = '';

// Абсолютный адрес корня сервера, не должен оканчиваться на слэш.
define('DOCUMENT_ROOT', getenv('SITE_ROOT') ? getenv('SITE_ROOT') : $_SERVER['DOCUMENT_ROOT']);

// В пути поиска по умолчанию включаем корень сайта, путь к Ideal и папке кастомизации CMS
set_include_path(
    get_include_path()
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Custom/'
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Modules/Ideal/'
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Ideal/'
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Custom/Modules/'
    . PATH_SEPARATOR . DOCUMENT_ROOT . $subFolder . '/' . $cmsFolder . '/Modules/'
);

// Подключаем автозагрузчик классов
//require_once 'Image2.php';

require_once 'Core/AutoLoader.php';

use Ideal\Core;

// Подключаем класс конфига
$config = Core\Config::getInstance();

// Каталог, в котором находятся модифицированные скрипты CMS
$config->cmsFolder = $subFolder . $cmsFolder;

// Куда будет вестись лог ошибок. Варианты file|display|comment|firebug|email
$config->errorLog = 'firebug';

// Загружаем список структур из конфигурационных файлов структур
$config->loadSettings();

use Ideal\Structure\User;

class Transfer
{
    public $config = array();
    private $tmpDir = '';

    function __construct()
    {
        //$this->config = parse_ini_file('ini.ini');
        $this->tmpDir = DOCUMENT_ROOT . '/tmp/1c/';
        // Проверка существования папки для временных данных
        if (!file_exists($this->tmpDir)) {
            mkdir($this->tmpDir, 0777, true);
        }
    }


    public function get($text)
    {
        switch ($_GET["$text"]) {
            case 'checkauth':
                // Авторизация 1с
                $login = new User\Model();
                if ($login->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
                    print "success\n";
                    print session_name() . "\n";
                    print session_id();
                }

                return 0;
                break;
            case 'init':
                // Очистка прошлых данных(если остались)
                $tmp_files = glob($this->tmpDir . '*.*');
                if (is_array($tmp_files))
                    foreach ($tmp_files as $v) {
                        unlink($v);
                    }
                print "zip=no\n";
                print "file_limit=1000000\n";
                return 0;
                break;
            case 'file':
                // Получение данных из 1с
                $filename = basename($_GET['filename']);
                if ($filename == 'import.xml' OR $filename == 'offers.xml') {
                    $dir = '';
                } else {
                    $dir = str_replace('/' . $filename, '', $_GET['filename']);
                }

                if (!file_exists($this->tmpDir . '' . $dir)) {
                    mkdir($this->tmpDir . '' . $dir, 0755, true);
                }

                $f = fopen($this->tmpDir . '' . $dir . '/' . $filename, 'ab');
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
                print "success";
                break;
            default:
                // Если пришел не 1с
                break;
        }
        return $_GET[$text];
    }

}

session_start();
$tool = new Transfer();
$tmp = $tool->get('mode');
if ($tmp) {
    $i = new \Shop\Structure\Service\Load1c\Image($tmp, 150, 150, 'big', false);
}

//$base = new \Shop\Structure\Service\Load1c\Tools();
$import = $tool->config['import'];
$offers = $tool->config['offers'];
$priceId = $tool->config['priceId'];
//$base->loadBase($import, $offers, $priceId);

