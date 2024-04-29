<?php

namespace Shop\Structure\Service\Load1CV3;

class ModelAbstractFactory
{
    protected array $config;

    /**
     * Определяет модель и на основании имени файла
     *
     * @param string $filename Имя файла для обработки которого определяется модель
     * @return ModelInterface
     */
    public function createByFilename(string $filename): ModelInterface
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
                return new $model($this->getConfig());
            }
        }

        return new $model();
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }
}