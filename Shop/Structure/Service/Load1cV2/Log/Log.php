<?php
namespace Shop\Structure\Service\Load1cV2\Log;

/**
 * Обеспечивает логирование процесса обмена данными с 1С
 */
class Log
{
    /** @var string Сообщение, которое будет добавлено в лог */
    protected $logMessage = '';

    /**
     * Устанавливает сообщение, которое будет помещено в лог
     *
     * @param string $message Сообщение, которое будет помещено в лог
     */
    public function setLogMessage($message)
    {
        $this->logMessage = $message;
    }

    /**
     * Добавляет текст к сообщению, которое будет помещено в лог
     *
     * @param string $message Текст, добавляемый к сообщению, которое будет помещено в лог
     */
    public function appendToLogMessage($message)
    {
        $this->logMessage .= $message;
    }

    /**
     * Добавляет информацию в файл логирования
     */
    public function addToLog()
    {
        $fp = fopen(__DIR__ . DIRECTORY_SEPARATOR . 'log1c.log', 'a');
        fwrite($fp, $this->logMessage);
        fwrite($fp, "\n---------------------------------------------------------------------------\n");
        fclose($fp);
    }

    /**
     * Обработчик обычных ошибок скриптов в процессе обмена данными с 1С
     *
     * @param int $errno   Номер ошибки
     * @param string $errstr  Сообщение об ошибке
     * @param string $errfile Имя файла, в котором была ошибка
     * @param int $errline Номер строки на которой произошла ошибка
     */
    public function logErrorHandler($errno, $errstr, $errfile, $errline)
    {
        $_err = 'Ошибка [' . $errno . '] ' . $errstr . ', в строке ' . $errline . ' файла ' . $errfile;
        $this->appendToLogMessage($_err . "\n");
    }

    /**
     * Обработчик, вызываемый при завершении работы скрипта в процессе обмена данными с 1С
     */
    public function logShutdownFunction()
    {
        $error = error_get_last();
        $errors = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING);
        if (in_array($error['type'], $errors)) {
            $_err = 'Ошибка ' . $error['message'] . ', в строке ' . $error['line'] . ' файла ' . $error['file'];
            $this->appendToLogMessage($_err . "\n");
            $this->addToLog();
        }
    }
}
