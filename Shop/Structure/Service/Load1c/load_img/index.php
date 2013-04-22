<?php

require_once 'image.php';
class Transfer
{
    private $config = array();
    private $tmpDir = '';

    function __construct()
    {
        $this->config = parse_ini_file('ini.ini');
        $this->tmpDir = (isset($this->config['tmp_dir'])) ? $this->config['tmp_dir'] : "tmp/1c/";
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
                print "success\n";
                print session_name() . "\n";
                print session_id();
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
                $f = fopen($this->tmpDir . $filename, 'ab');
                fwrite($f, file_get_contents('php://input'));
                fclose($f);
                print "success\n";
                if ($filename == 'import.xml' OR $filename == 'offers.xml' OR $this->config['manual'] == 1) return 0;
                return $filename;
                break;
            case 'import':
                print "success";
                break;
            default:
                // Если пришел не 1с
                print 'error';
                break;
        }
        return $_GET[$text];
    }

}

session_start();
$tool = new Transfer();
$tmp = $tool->get('mode');
if ($tmp) {
    $i = new Image($tmp);
}
