<?php
namespace Shop\Structure\Service\Load1CV3;

use Ideal\Core\Request;
use Ideal\Core\Config;
use Ideal\Structure\User\Model as UserModel;
use Shop\Structure\Service\Load1CV3\Log\Log;
use Shop\Structure\Service\Load1CV3\Models\OrderModel;

class FrontController
{
    /** @var array Настройки для обмена данными с 1С */
    protected $config = array();

    /** @var array Массив с данными о текущем состоянии процесса выгрузки */
    protected $tmpResult;

    /** @var string/array Ответ (в виде строки или массива) на обработку запроса */
    protected $response;

    /** @var \Shop\Structure\Service\Load1CV3\Log\Log Класс для логирования процесса обмена данными */
    protected $logClass;

    /** @var string Текст для хранения в логе */
    protected $logMessage;

    /**
     * Инициирует класс с заданными настройками
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;

        // Считываем результаты работы предыдущих этапов обработки
        $cmsConfig = Config::getInstance();
        $tmpResultFile = DOCUMENT_ROOT . $cmsConfig->cms['tmpFolder'] . DIRECTORY_SEPARATOR . 'tmpResult';
        $this->tmpResult = array();

        if (file_exists($tmpResultFile)) {
            $this->tmpResult = file_get_contents($tmpResultFile);
            $this->tmpResult = json_decode($this->tmpResult, true);
        } else {
            touch($tmpResultFile);
        }

        $dateTime = date('d.m.Y/H:i:s');
        $postData = http_build_query($_POST);
        $sessionData = '';

        if (isset($_SESSION)) {
            $sessionData = http_build_query($_SESSION);
        }

        $cookieData = http_build_query($_COOKIE);
        $logMessage = <<<LOGMESSAGE
Дата/время: {$dateTime}
       Запрос: {$_SERVER['QUERY_STRING']}
       POST-данные: {$postData}
       SESSION-данные: {$sessionData}
       COOKIE-данные: {$cookieData}
LOGMESSAGE;
        $this->logClass = new Log();
        if ($this->config['keep_log'] == 1) {
            $this->logClass->log('info', $logMessage);
        }
    }

    public function __destruct()
    {
        if (is_array($this->response) && isset($this->response['tmpResult'])) {
            $this->tmpResult = array_merge($this->tmpResult, $this->response['tmpResult']);
        }
        $cmsConfig = Config::getInstance();
        $tmpResultFile =DOCUMENT_ROOT . $cmsConfig->cms['tmpFolder'] . DIRECTORY_SEPARATOR . 'tmpResult';
        file_put_contents($tmpResultFile, json_encode($this->tmpResult));
        if ($this->config['keep_log'] == 1) {
            $this->logMessage .= "\n------------------------------------------------------------------------\n\n";
            $this->logClass->log('info', $this->logMessage);
        }
    }

    /**
     * Запуск FrontController'а
     */
    public function run()
    {
        $request = new Request();
        $actionName = $request->mode . 'Action';
        if (!method_exists($this, $actionName)) {
            throw new \BadMethodCallException('Не существует метода "' . $actionName . '" для обработки запроса');
        }
        $this->$actionName();
        if (empty($request->par)) {
            $this->printResponse();
        } else {
            return $this->response;
        }
    }

    /**
     * Реакция на запрос деактивации товаров отстутствующих в полной выгрузке
     */
    protected function deactivateAction()
    {
        $this->response = "success\n";
    }

    /**
     * Реакция на уведомление от 1С об успешности обработки информации на своей стороне
     */
    protected function successAction()
    {
        $this->response = "success\n";
    }

    /**
     * Запускает процесс сбора данных по заказам для отдачи по запросу
     */
    protected function queryAction()
    {
        $order = new OrderModel();
        $xml = $order->generateExportXml();
        header("Content-type: text/xml; charset=windows-1251");
        $this->response = trim($xml);
    }

    /**
     * Запускает процесс обработки файла
     *
     * @return bool Флг (не)успешного завершения работы метода
     * @throws \ReflectionException
     */
    protected function importAction()
    {
        $request = new Request();
        $filename = basename($request->filename);

        // Ищем файл запрошенный для обработки
        $workDir = DOCUMENT_ROOT . $this->config['directory_for_processing'];
        if (!empty($request->workDir)) {
            $workDir = $request->workDir;
        }
        $exist = ExchangeUtil::checkFileExist($workDir, $filename);
        if (!$exist) {
            $this->response = "failure\nНе найден файл для обработки";
            return false;
        }

        // Если запрошенный для обработки файл найден на сервере, то получаем модель, которая будет заниматься его
        // обработкой
        $model = $this->getModel($filename);

        // Првоеряем является ли запрос началом нового сеанса обмена
        $cmsConfig = Config::getInstance();
        $tmpResultFile = DOCUMENT_ROOT . $cmsConfig->cms['tmpFolder'] . DIRECTORY_SEPARATOR . 'tmpResult';
        $newSeance = ExchangeUtil::checkExchangeStart($workDir . $filename, $tmpResultFile);

        // Пытаемся получить информацию о полноте выгрузки
        $cmsConfig->isOnlyUpdate = ExchangeUtil::checkUpdateInfo($workDir . $filename);

        if ($newSeance) {
            // Запускаем процесс подготовки базы для приёма данных если временный файл обновлялся более 1,5 минут назад
            ExchangeUtil::prepareTables();
            $this->tmpResult = array();
        }

        $path = ExchangeUtil::getLastPackageFolder(DOCUMENT_ROOT . $this->config['directory_for_keeping']);
        $packageNum = (int)substr($path, strrpos($path, '/') + 1) + 1;

        $response = $model->startProcessing($workDir . $filename, $packageNum);

        // Если в настройках указана надобность сохранения файлов выгрузки, то запускаем процесс переноса файлов.
        // Этот процесс не нужно запускать если происходит ручная выгрузка данных.
        if (empty($request->par)) {
            if ($this->config['keep_files'] == 1) {
                $fromDir = DOCUMENT_ROOT . $this->config['directory_for_processing'];
                $toDir = DOCUMENT_ROOT . $this->config['directory_for_keeping'];
                if ($newSeance) {
                    ExchangeUtil::purge($toDir);
                }
                ExchangeUtil::transferFilesWthStructureSaving($fromDir, $toDir);
            } else {
                unlink($workDir . $filename);
            }
        }
        if (is_array($response)) {
            if (!isset($response['status'])) {
                array_unshift($response, 'success');
            } else {
                array_unshift($response, $response['status']);
            }
        } else {
            $response = "success\n" . $response;
        }
        $this->response = $response;
        return true;
    }

    /**
     * Запускает процесс сохранения файла
     */
    protected function fileAction()
    {
        $request = new Request();
        $request->filename = str_replace('\\', '/', $request->filename);
        $filename1 = basename($request->filename);
        $dirName = ltrim(str_replace($filename1, '', $request->filename), '/');
        $filename = DOCUMENT_ROOT . $this->config['directory_for_processing'] . $dirName . $filename1;

        // Проверяем надобность добавления передаваемого файла к уже существующему
        $needingAdd = ExchangeUtil::checkNeedingAdd($filename);

        $mode = 'ab';
        if (!$needingAdd) {
            $mode = 'wb';
        }

        ExchangeUtil::createFolder(DOCUMENT_ROOT . $this->config['directory_for_processing'] . $dirName);

        // Сохраняем файл из потока
        ExchangeUtil::saveFileFromStream($filename, $mode);

        // Делаем бэкап переданных файлов для целей отладки
        //$backupFile = DOCUMENT_ROOT . rtrim($this->config['directory_for_processing'], '/') . '_backup/' . $dirName . $filename1;
        //copy($filename, $backupFile);

        // Если передан файл отчёта, то запускаем процесс применения информации из временных таблиц и удаляем файл
        // отчёта
        if (stripos($filename, 'reports') !== false) {
            if ($this->config['keep_files']) {
                $newFileName = DOCUMENT_ROOT . $this->config['directory_report'] . basename($filename);
                rename($filename, $newFileName);
            } else {
                unlink($filename);
            }
            $this->tmpResult = array();
            ExchangeUtil::finalUpdates();
            $workDir = DOCUMENT_ROOT . $this->config['directory_for_processing'];
            ExchangeUtil::purge($workDir);
            ExchangeUtil::createFolder($workDir);
            if ($this->config['keep_log'] == 1) {
                $this->logMessage .= "\n" . str_repeat('-', 70) ."\n\n";
                $this->logClass->log('info', $this->logMessage);

                $this->logClass->copySuccessLog();
                $this->logClass->clearLog();

                // Информацию о том что пришёл отчёт не нужно записывать дважды в лог
                $this->config['keep_log'] = 0;
            }
        }

        $this->response = "success\n";
    }

    /**
     * Отдаёт конфигурационные данные для инициации обмена с 1С
     */
    protected function initAction()
    {
        $fileSize = (int)$this->config['filesize'] * 1024 * 1024;
        $useZip = 'no';
        if (isset($this->config['enable_zip'])) {
            $useZip = $this->config['enable_zip'] ? 'yes' : 'no';
        }
        $this->response = "zip={$useZip}\n";
        $this->response .= "file_limit={$fileSize}\n";
        $this->response .= "sessionKey=sessionToken\n";
        // 1С ищет версию схемы в четвёртой строке при обмене заказами
        $this->response .= 'schema_version = 2.08';
        $fileSize = ExchangeUtil::humanFilesize($fileSize);
        $this->logMessage .= <<<LOGMESSAGE
        
       Установлены параметры для обмена данными.
       Использовать архивирование - {$useZip}
       Ограничение размера принимаемого файла - {$fileSize}
LOGMESSAGE;
    }

    /**
     * Реакция на запрос проверки доступа для обмена
     */
    protected function checkauthAction()
    {
        $user = new UserModel();
        if ($user->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            // удаление файлов от предыдущей выгрузки
            if (empty($this->tmpResult) && $this->config['keep_files'] == 1) {
                ExchangeUtil::purge(DOCUMENT_ROOT . $this->config['directory_for_keeping']);
                $firstPackageFolder = rtrim(DOCUMENT_ROOT . $this->config['directory_for_keeping'], '/');
                $firstPackageFolder .= DIRECTORY_SEPARATOR . '1' . DIRECTORY_SEPARATOR;
                ExchangeUtil::createFolder($firstPackageFolder);
            }

            // признак успешности
            $this->response = "success\n";
            // имя куки файла
            $this->response .= session_name() . "\n";
            // значение куки файла
            $this->response .= session_id();
            $this->logMessage .= "\n       Авторизация прошла успешно.";
        } else {
            // признак провала авторизации
            $this->response = "failure\n";
            $this->response .= "Ошибка аутентификации.\n";
            $this->response .= "Пользователь: {$_SERVER['PHP_AUTH_USER']}.\n";
            $this->response .= "Пароль: {$_SERVER['PHP_AUTH_PW']}.\n";
            $this->logMessage .= "\n       Ошибка авторизации, проверьте правильность логина и пароля.";
        }
    }

    /**
     * Отдаёт ответ о результате обработки запроса
     */
    protected function printResponse()
    {
        if (is_array($this->response)) {
            $printResponse = array_slice($this->response, 0, 3);
            $printResponse = implode("\n", $printResponse);
            $printResponse = str_replace('<br />', "\n", $printResponse);
            echo $printResponse;
        } else {
            echo $this->response;
        }
    }

    /**
     * Определяет модель и на основании имени файла
     *
     * @param string $filename Имя файла для обработки которого определяется модель
     * @return mixed
     * @throws \ReflectionException
     */
    protected function getModel($filename)
    {
        preg_match('/(\w*?)_/', $filename, $type);
        if (!isset($type[1])) {
            throw new \RuntimeException(sprintf('Файл "%s" не может быть обработан', $filename));
        }

        $model = 'Shop\\Structure\\Service\\Load1CV3\\Models\\' . ucfirst($type[1]) . 'Model';
        if (!class_exists($model)) {
            throw new \RuntimeException(sprintf('Не найдена модель для обработки файла "%s"', $filename));
        }

        $class = new \ReflectionClass($model);
        $constructor = $class->getConstructor();
        if ($constructor) {
            $parameters = $constructor->getParameters();
            if ($parameters && $parameters[0]->name === 'exchangeConfig') {
                return new $model($this->config);
            }
        }
        return new $model();
    }
}