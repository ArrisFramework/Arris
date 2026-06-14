<?php
declare(strict_types=1);

namespace Arris;

use Arris\Core\Config\AbstractConfig;
use Arris\Core\Config\Config;
use Arris\Core\Config\Exception\EmptyDirectoryException;
use Arris\Core\Config\Exception\FileNotFoundException;
use Arris\Core\Config\Exception\UnsupportedFormatException;

class AppConfig extends AbstractConfig
{
    private static array $instances = [];

    /**
     * @throws UnsupportedFormatException
     * @throws FileNotFoundException
     * @throws EmptyDirectoryException
     */
    public function __construct(array $files = [], array $defaults = [])
    {
        parent::__construct($files);

        // Читаем файлы с диска
        $loadedData = (new Config($files))->data;

        // Сливаем дефолты конкретного App с загруженными файлами
        $this->data = self::arrayMergeRecursiveReplace($defaults, $loadedData);
    }

    protected static function arrayMergeRecursiveReplace(array $original, array $patch): array
    {
        foreach ($patch as $key => $value) {
            if ($value === null) {
                unset($original[$key]);
            } elseif (is_array($value) && isset($original[$key]) && is_array($original[$key])) {
                $original[$key] = self::arrayMergeRecursiveReplace($original[$key], $value);
            } else {
                $original[$key] = $value;
            }
        }
        return $original;
    }

    /**
     * Добавляет массив конфигурации к существующим данным.
     * Используется для динамического расширения конфига.
     *
     * @param array $config Массив для слияния
     * @return self
     */
    public function add(array $config): self
    {
        $this->data = self::arrayMergeRecursiveReplace($this->data, $config);
        return $this;
    }

    /**
     * Заменяет данные конфига целиком.
     *
     * @param array $config Новый массив конфигурации
     * @return self
     */
    public function replace(array $config): self
    {
        $this->data = $config;
        return $this;
    }
}