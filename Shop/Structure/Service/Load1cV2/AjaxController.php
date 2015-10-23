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

        // Чистим строки от возможных лишних пробелов перед сохранением в файл
        $dataToSave = array();
        array_walk($_POST, function ($v, $k) use (&$dataToSave) {
            $dataToSave[$k] = trim($v);
        });
        $conf = array_merge($configFile, array('info' => $dataToSave));
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
        if (isset($_POST['packageNum'])) {
            $packageNum = (int) $_POST['packageNum'];
        } else {
            $packageNum = 1;
        }

        if (isset($_POST['fixStep']) && $_POST['fixStep'] != 'false') {
            $fixStep = true;
        } else {
            $fixStep = false;
        }

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
                    $answer['nextStep'] = 2;
                    $answer['infoText'] = 'Подготовка базы';
                    $answer['successText'] = 'База готова для внесения изменений';
                    break;
                case 2:
                    // Получаем список всех файлов и папок из папки выгрузки
                    $fc->loadFiles($configFile['info']['directory']);
                    // Обработка категорий/групп товара из общего файла import.xml
                    $answer = array_merge($answer, $fc->category());
                    $answer['nextStep'] = 3;
                    break;
                case 3:
                    $fc->loadFiles($configFile['info']['directory']);
                    // Обработка товаров из указанного пакета/папки
                    $answer = array_merge($answer, $fc->good($packageNum));
                    $answer['nextStep'] = 4;
                    $answer['infoText'] = 'Обработка товаров из пакета №' . $packageNum;
                    break;
                case 4:
                    $fc->loadFiles($configFile['info']['directory']);
                    //$answer = array_merge($answer, $fc->directory());
                    $answer['nextStep'] = 5;
                    $answer['infoText'] = 'Четвёртый шаг';
                    $answer['successText'] = 'Четвёртый шаг пройден';
                    break;
                case 5:
                    $fc->loadFiles($configFile['info']['directory']);
                    $answer = array_merge($answer, $fc->offer($packageNum));
                    $answer['nextStep'] = 6;
                    $answer['infoText'] = 'Обработка товарных предложений из пакета №' . $packageNum;
                    break;
                case 6:
                    $fc->loadFiles($configFile['info']['directory']);
                    $answer = array_merge($answer, $fc->loadImages($configFile['info'], $packageNum));
                    $answer['infoText'] = 'Обработка изображений из пакета №' . $packageNum;
                    $countPackages = $fc->getCountPackages();
                    if ($packageNum < $countPackages) {
                        $packageNum++;
                        $answer['packageNum'] = $packageNum;
                        if (!$fixStep) {
                            $answer['nextStep'] = 3;
                        } else {
                            $answer['nextStep'] = 6;
                        }
                    } else {
                        $answer['nextStep'] = 7;
                    }
                    break;
                case 7:
                    $answer['infoText'] = 'Завершение выгрузки';
                    $answer['successText'] = 'Выгрузка завершена успешно';
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
