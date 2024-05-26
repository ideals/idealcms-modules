<?php

namespace Shop\Structure\Service\Load1CV3;

abstract class ModelAbstract implements ModelInterface
{
    /** @var array Массив содержащий структурированный ответ по факту обработки файла */
    protected array $answer = [
        'infoText' => 'Не переопределён текст для файла из пакета № %s',
        'successText' => 'Добавлено: %d<br />Обновлено: %d',
        'add' => 0,
        'update' => 0
    ];

    /** @var int Порядок при загрузке данных обмена с 1С из файлов */
    protected int $sort = 0;

    protected string $filename;

    protected int $packageNum = 0;

    /** @var array Общие настройки для всего процесса обмена */
    public array $exchangeConfig = [];

    protected bool $isOnlyUpdate = false;

    public function __construct(array $exchangeConfig, string $filename)
    {
        $this->exchangeConfig = $exchangeConfig;
        $this->filename = $filename;
        $this->init();
    }

    public function isOnlyUpdate(): bool
    {
        return $this->isOnlyUpdate;
    }

    /**
     * Возвращаем ответ пользователю о проделанной работе
     *
     * @return array ответ пользователю 'add'=>count(), 'update'=>count()
     */
    public function answer(): array
    {
        // Определяем пакет для отдачи правильного текста в ответе
        $this->answer['infoText'] = sprintf(
            $this->answer['infoText'],
            $this->packageNum === 0 ? 'из пакета № ' . $this->packageNum : ''
        );

        $this->answer['successText'] = sprintf(
            $this->answer['successText'],
            $this->answer['add'],
            $this->answer['update']
        );

        return $this->answer;
    }

    /**
     * @param string $infoText
     * @return void
     */
    protected function setInfoText(string $infoText): void
    {
        $this->answer['infoText'] = $infoText;
    }

    public function getSort(): int
    {
        if ($this->sort === 0) {
            throw new \RuntimeException('Не определён порядок сортировки для файла ' . $this->filename);
        }
        return $this->sort;
    }

    protected function setSort(int $sort): void
    {
        $this->sort = $sort;
    }
}