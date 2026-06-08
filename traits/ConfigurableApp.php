<?php

namespace Arris\Traits;

trait ConfigurableApp {
    public static \Arris\AppConfig $config;

    protected static function getDefaultConfig(): array
    {
        return [];
    }

    public static function init(array $files): void
    {
        // Создаем временный конфиг для загрузки из файлов
        $tempConfig = new \Arris\AppConfig($files);

        $merged = \Arris\AppConfig::array_merge_recursive_replace(
            static::getDefaultConfig(),
            $tempConfig->data
        );

        // Создаем новый объект, но передаем пустой массив файлов
        self::$config = new \Arris\AppConfig([]);

        // И заменяем данные напрямую
        self::$config->data = $merged;
    }
}