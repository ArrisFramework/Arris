<?php
declare(strict_types=1);

namespace Arris;

use Arris\Core\Config\AbstractConfig;
use Arris\Core\Config\Config;

class AppConfig extends AbstractConfig
{
    private static array $instances = [];

    final private function __construct(array $files = [], array $defaults = [])
    {
        parent::__construct($files);

        // ВОЗВРАЩАЕМ парсинг через конкретный класс Config
        $loadedData = (new Config($files))->data;

        $this->data = self::arrayMergeRecursiveReplace($defaults, $loadedData);
    }

    public static function getInstance(array $files = [], array $defaults = []): static
    {
        $class = static::class;
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static($files, $defaults);
        }
        return self::$instances[$class];
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
}