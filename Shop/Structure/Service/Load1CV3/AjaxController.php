<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Shop\Structure\Service\Load1CV3;

use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Core\Util;
use Shop\Structure\Service\Load1CV3\Models\ImportModel;

/**
 * Контроллер, вызываемый при загрузке из файлов через админку
 *
 * @property array configFile Массив настроек обмена с 1С
 * @property string filename Файл для обработки
 */
class AjaxController extends \Ideal\Core\AjaxController
{

    public function __construct()
    {
        $configs = Config::getInstance();

        $cmsFolderPath = DOCUMENT_ROOT . DIRECTORY_SEPARATOR . $configs->cmsFolder . DIRECTORY_SEPARATOR;
        $settingsFilePath = $cmsFolderPath . 'load1CV3Settings.php';

        // Если нет файла в папке админки, то копируем его туда из папки модуля
        if (!file_exists($settingsFilePath)) {
            $settingsFilePath = $cmsFolderPath . 'Mods/Shop/Structure/Service/Load1CV3/load1CV3Settings.php';
            if (!file_exists($settingsFilePath)) {
                // Если файла настроек нет и в папке модуля то выбрасываем исключение
                throw new \RuntimeException('Отсутствует файл настроек модуля выгрузки');
            }
        }

        $this->configFile = include($settingsFilePath);

        $this->filename = (string) $_POST['filename'];
    }

    /**
     * Экшен запуска получения файла для обработки
     *
     * @return string json-массив с результатами выполненного шага загрузки
     * @throws \Exception
     */
    public function getFileAction()
    {
        $answer = [
            'errors' => [],
            'workDir' => '',
            'response' => [
                'infoText' => '',
            ],
        ];
        $request = new Request();
        $dirToScan = DOCUMENT_ROOT . $this->configFile['directory_for_keeping'];
        $onlyImageResize = false;
        if ($request->onlyImageResize && (string)$request->onlyImageResize === 'true') {
            $onlyImageResize = true;
        }

        // Если требуется только обработка картинок, то оставляем только пути до папок с картинками
        if ($onlyImageResize) {
            $dirToSearch = $this->configFile['images_directory'];
            $exchangeFiles = ExchangeUtil::getAllImagesFolder($dirToScan, $dirToSearch);
        } else {
            // Получаем все файлы которые предоставлены для обработки
            $exchangeFiles = ExchangeUtil::getAllExchangeFiles($dirToScan);
        }

        $answer['filename'] = '';

        // Если имя файла для обработки ещё не передано значит берём первый файл из выгрузки
        if (!$this->filename) {
            $answer['workDir'] = $answer['filename'] = current($exchangeFiles);
            if (!$onlyImageResize) {
                $key = key($exchangeFiles);
                $answer['workDir'] = str_replace(basename($key), '', $key);
            }
        } else {
            $next = false;
            foreach ($exchangeFiles as $key => $value) {
                if ($next) {
                    $answer['workDir'] = $answer['filename'] = $value;
                    if (!$onlyImageResize) {
                        $answer['workDir'] = str_replace(basename($key), '', $key);
                    }
                    break;
                }
                if ($value == $this->filename) {
                    $next = true;
                }
            }
        }

        // Если все файлы обработаны, то нужно применить изменения к боевым таблицам
        if ($answer['filename'] === '') {
            if (!$onlyImageResize) {
                ExchangeUtil::finalUpdates();
            }
        } elseif ($onlyImageResize) {
            $dirParts = explode('/', $answer['filename']);
            $packageNum = (int) $dirParts[count($dirParts) - 3];
            $answer['response']['infoText'] = 'Обработка картинок из пакета № ' . $packageNum;
        } else {
            $config = require __DIR__ . '/load1cV3Settings.php'; // todo обнаружение в папке Mod.s
            $model = (new ModelAbstractFactory())->setConfig($config)->createByFilename($answer['workDir'] . $answer['filename']);
            $modelAnswer = $model->answer();

            // Получаем заголовок для вывода на странице ручного обновления
            preg_match('/(\w*?)_/', $answer['filename'], $matches);

            $dir = trim(str_replace(DOCUMENT_ROOT, '', $answer['workDir']), '/');
            $packageNum = 0;
            if (substr_count($dir, '/') > 3) {
                $dirParts = explode('/', $dir);
                $packageNum = (int) end($dirParts);
            }
            $answer['response']['infoText'] .= sprintf($modelAnswer['infoText'], $packageNum);
        }

        return json_encode($answer);
    }

    /**
     * Экшен запуска файла на обработку
     *
     * @return string json-массив с результатами выполненного шага загрузки
     * @throws \Exception
     */
    public function importFileAction()
    {
        $answer = array(
            'errors' => array(),
        );
        $request = new Request();
        $workDir = (string)$request->workDir;

        $onlyImageResize = false;
        if ($request->onlyImageResize && (string)$request->onlyImageResize === 'true') {
            $onlyImageResize = true;
        }

        if (!$onlyImageResize) {
            $fc = new FrontController($this->configFile);
            $request->filename = $this->filename;
            $request->workDir = $workDir;
            $request->mode = 'import';

            try {
                $answer['response'] = $fc->run();
                $answer['filename'] = $this->filename;
            } catch (\RuntimeException $e) {
                $answer['errors'][] = $e->getMessage() . "<pre>" . $e->getTraceAsString() . "</pre>";
                return json_encode($answer);
            }
        } else {
            try {
                $answer['filename'] = $this->filename;
                $importModel = new ImportModel($this->configFile);
                $importModel->loadImages($workDir, true);
                $answer['response'] = $importModel->answer();
                array_unshift($answer['response'], 'success');
            } catch (\RuntimeException $e) {
                $answer['errors'][] = $e->getMessage() . "<pre>" . $e->getTraceAsString() . "</pre>";
                return json_encode($answer);
            }
        }

        $errors = Util::$errorArray;
        if (count($errors) > 0) {
            $answer['errors'] = $errors;
        }

        return json_encode($answer);
    }
}
