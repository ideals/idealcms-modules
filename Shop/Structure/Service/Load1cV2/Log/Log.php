<?php
namespace Shop\Structure\Service\Load1cV2\Log;

use Ideal\Core\Config;

/**
 * Обеспечивает логирование процесса обмена данными с 1С
 */
class Log
{

    /**
     * @var string Путь до файла логирования
     */
    protected $logFilePath;

    /**
     * @var string Путь до файла лога успешной выгрузки
     */
    protected $successLogFilePath;

    public function __construct()
    {
        $config = Config::getInstance();
        $logFilePath = DOCUMENT_ROOT . DIRECTORY_SEPARATOR . $config->cms['tmpFolder'] . DIRECTORY_SEPARATOR;
        $logFilePath .= 'log1c.log';

        // Если файла логирования ещё нет, то создаём его
        if (!file_exists($logFilePath)) {
            if (!touch($logFilePath)) {
                throw new \Exception('Не удалось создать файл для хранения логов');
            }
        }
        $this->logFilePath = $logFilePath;

        $successLogFilePath = DOCUMENT_ROOT . DIRECTORY_SEPARATOR . $config->cms['tmpFolder'] . DIRECTORY_SEPARATOR;
        $successLogFilePath .= 'log1cSuccess.log';

        // Если файла логирования ещё нет, то создаём его
        if (!file_exists($successLogFilePath)) {
            if (!touch($successLogFilePath)) {
                throw new \Exception('Не удалось создать файл для хранения хранения логов успешной выгрузки');
            }
        }

        $this->successLogFilePath = $successLogFilePath;
    }

    /**
     * Авария, система неработоспособна.
     *
     * @param string $message
     * @param array $context
     */
    public function emergency($message, array $context = array())
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * Тревога, меры должны быть предприняты незамедлительно.
     *
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = array())
    {
        $this->log('alert', $message, $context);
    }

    /**
     * Критическая ошибка, критическая ситуация.
     *
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = array())
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Ошибка на стадии выполнения, не требующая неотложного вмешательства,
     * но требующая протоколирования и дальнейшего изучения.
     *
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = array())
    {
        $this->log('error', $message, $context);
    }

    /**
     * Предупреждение, нештатная ситуация, не являющаяся ошибкой.
     *
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = array())
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Замечание, важное событие.
     *
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = array())
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Информация, полезные для понимания происходящего события.
     *
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = array())
    {
        $this->log('info', $message, $context);
    }

    /**
     * Информация, полезные для понимания происходящего события.
     *
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = array())
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Протоколирование с произвольным уровнем.
     *
     * @param string $level Константа одного из уровней протоколирования
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = array())
    {
        $replace = array();
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        $message = strtr($message, $replace);

        $fp = fopen($this->logFilePath, 'a');
        fwrite($fp, "{$level} - {$message}\n");
        fclose($fp);
    }

    /**
     * Очищает лог
     */
    public function clearLog()
    {
        file_put_contents($this->logFilePath, '');
    }

    /**
     * Копирует текущий лог в файл лога успешной выгрузки
     */
    public function copySuccessLog()
    {
        $currentLogData = file_get_contents($this->logFilePath);
        file_put_contents($this->successLogFilePath, $currentLogData);
    }

    /**
     * Обработчик обычных ошибок скриптов в процессе обмена данными с 1С
     *
     * @param int $number Номер ошибки
     * @param string $message Сообщение об ошибке
     * @param string $file Имя файла, в котором была ошибка
     * @param int $line Номер строки на которой произошла ошибка
     */
    public function logErrorHandler($number, $message, $file, $line)
    {
        $logMessage = 'Ошибка {number} {message}, в строке {$line} файла {file}';
        $context = array(
            'number' => $number,
            'message' => $message,
            'file' => $file,
            'line' => $line,
        );
        $this->error($logMessage, $context);
    }

    /**
     * Обработчик, вызываемый при завершении работы скрипта в процессе обмена данными с 1С
     */
    public function logShutdownFunction()
    {
        $error = error_get_last();
        $errors = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING);
        if (in_array($error['type'], $errors)) {
            $logMessage = 'Ошибка {message}, в строке {line} файла {file}';
            $context = array(
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
            );
            $this->error($logMessage, $context);
        }
    }
}
