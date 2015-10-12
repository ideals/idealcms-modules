<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Shop\Structure\Service\Load1cV2;

use Ideal\Core\Config;
use Ideal\Core\Util;

/**
 * Контроллер, вызываемый при загрузке из файлов через админку
 */
class AjaxController extends \Ideal\Core\AjaxController
{
    public function ajaxUpdateSettingsAction()
    {
        if ($_POST['enable_zip'] == true) {
            $_POST['enable_zip'] = 'yes';
        } else {
            $_POST['enable_zip'] = 'no';
        }
        $folder = __DIR__;
        $configFile = include($folder . '/config.php');

        $conf = array_merge($configFile, array('info' => $_POST));
        $str = "<?php\n\nreturn ";
        file_put_contents($folder . '/config.php', $str . var_export($conf, true) . ';');

        return json_encode('success');
    }

    /**
     * Экшен запуска загрузки из файлов по шагам
     *
     * @return string json-массив с результатами выполненного шага загрузки
     */
    public function ajaxIndexLoadAction()
    {
        $fc = new FrontController();
        $answer = array(
            'continue'  => true,
            'errors'    => array(),
        );

        $configs = Config::getInstance();
        $step = (int) $_POST['step'];

        $folder = __DIR__;
        $configFile = include($folder . '/config.php');

        $configs->cms = array_merge($configs->cms, array('errorLog'=>'var'));
        try {
            switch ($step) {
                case 1:
                    // Подготовка к загрузке данных — создание временных таблиц
                    $fc->loadFiles($configFile['info']['directory']);
                    // Создание временных таблиц
                    $fc->prepareTables();
                    break;
                case 2:
                    // Получаем список всех файлов и папок из папки выгрузки
                    $fc->loadFiles($configFile['info']['directory']);
                    // Обработка категорий/групп товара из общего файла import.xml
                    $answer = array_merge($answer, $fc->category());
                    break;
                case 3:
                    $fc->loadFiles($configFile['info']['directory']);
                    // Обработка товаров из указанного пакета/папки
                    // todo зациклить шаги 3-6 до обработки всех пакетов
                    $answer = array_merge($answer, $fc->good(1));
                    break;
                case 4:
                    $fc->loadFiles($configFile['info']['directory']);
                    //$answer = array_merge($answer, $fc->directory());
                    break;
                case 5:
                    $fc->loadFiles($configFile['info']['directory']);
                    $answer['step'] = 'Предложения';
                    $answer = array_merge($answer, $fc->offer());
                    break;
                case 6:
                    $answer = array_merge($answer, $fc->loadImages($configFile['info']));
                    break;
                case 7:
                    $answer['continue'] = false;
                    $fc->renameTables();
                    break;
            }
        } catch (\RuntimeException $e) {
            $answer['continue'] = false;
            $answer['errors'][] = $e->getMessage() . "<pre>" . $e->getTraceAsString() . "</pre>";
            return json_encode($answer);
        }

        $errors = Util::$errorArray;
        if (count($errors) > 0) {
            $answer['continue'] = false;
            $answer['errors'] = $errors;
        }

        return json_encode($answer);
    }
}
