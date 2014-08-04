<?php
namespace Gallery\Structure\Photo\Admin;

use Ideal\Core\Db;
use Ideal\Core\Config;

class Model extends \Ideal\Structure\Roster\Admin\ModelAbstract
{
    public function getToolbar()
    {
        if (isset($_GET['directory']) && is_dir(DOCUMENT_ROOT . $_GET['directory'])) {

            // Получение списка полей БД для сохранения фотографий
            $fieldList = array();
            $prefList = array();
            foreach ($this->fields as $k => $f) {
                if (substr($k, 0, 4) == 'img_') {
                    $fieldList[] = $k;
                    $prefList[] = str_replace('img_', '', $k);
                }
            }

            $fieldList[] = 'img';
            $typeImg = '.jpg';
            $imageList = array();

            // Получение списка файлов в директории
            if ($handle = opendir(DOCUMENT_ROOT . $_GET['directory'])) {
                while (($file = readdir($handle)) !== false) {
                    if (!($file == '.' || $file == '..') && substr($file, -4) == $typeImg) {
                        $sizeFile = substr($file, -6);
                        $nameFile = substr($file, 0, -6);
                        $isBigPhoto = true;
                        foreach ($prefList as $f) {
                            if ($sizeFile == '-' . $f . $typeImg) {
                                $imageList[$nameFile]['img_' . $f] = $file;
                                $isBigPhoto = false;
                                break;
                            }
                        }
                        if ($isBigPhoto) {
                            $nameFile = substr($file, 0, -4);
                            $imageList[$nameFile]['img'] = $file;
                        }
                    }
                }
                closedir($handle);
            }

            // Удаление фотографий из списка, в котором нет большой фотографии или превьюшки
            foreach ($imageList as $k => $i) {
                if (!isset($i['img']) || !isset($i['img_s'])) {
                    unset($imageList[$k]);
                }
            }

            // Добавление фотографий
            if (count($imageList) > 0) {
                $this->addPhotoGallery($imageList, $fieldList);
            }

        }

        $input = '<input type="text" name="directory" value="" />';

        return $input;
    }

    /**
     * Добавление фотографий в базу данных
     * @param $imageList Список фотографий
     * @param $fieldList Список полей БД
     * return void
     */
    public function addPhotoGallery($imageList, $fieldList)
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $_sql = "SELECT * FROM {$this->_table}
                 WHERE structure_path = '{$this->structurePath}'";
        $list = $db->select($_sql);

        foreach ($list as $l) {
            $bigImg = str_replace($_GET['directory'] . '/', '', substr($l['img'], 0, -4));
            // Проверка наличия сохраненных фотогафий
            if (isset($imageList[$bigImg])) {
                unset($imageList[$bigImg]);
            }
        }
        $_sql  = '';
        $date = time();
        $fields = '';
        foreach ($fieldList as $f) {
            $fields .= '`' . $f . '`, ';
        }

        foreach ($imageList as $i) {
            $dir = $_GET['directory'] . '/';

            $images = '';
            foreach ($fieldList as $f) {
                if (isset($i[$f])) {
                    $images .= '\'' . $dir . $i[$f] . '\', ';
                } else {
                    $images .= '\'\',';
                }
            }

            $_sql = "INSERT INTO {$this->_table} (`structure_path`,
             	                                  `pos`,
             	                                  `name`,
             	                                  {$fields}
             	                                  `date_create`,
             	                                  `is_active`)
             	     VALUES ('{$this->structurePath}', '1', 'Toolbar',
             	             {$images} {$date}, 1);";
            $db->query($_sql);
        }
        if ($_sql != '') {
            header('Location: /' . $config->cmsFolder . '/index.php?par=' . $_GET['par']);
        }
    }

}
