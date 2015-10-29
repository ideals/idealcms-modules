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
     * Добавляет информацию в файл логирования
     */
    public function addToLog()
    {
        $fp = fopen(__DIR__ . DIRECTORY_SEPARATOR . 'log1c.log', 'a');
        fwrite($fp, $this->logMessage);
        fwrite($fp, "\n---------------------------------------------------------------------------\n");
        fclose($fp);
    }
}
