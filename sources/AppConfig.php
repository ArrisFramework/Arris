<?php

namespace Arris;

use Arris\Core\Config\AbstractConfig;
use Arris\Core\Config\Config;

class AppConfig extends AbstractConfig
{
    private static AppConfig $instance;

    public static function getInstance($config = [], $options = [], $services = []): ?AppConfig
    {
        if (!self::$instance) {
            // not self!!! later static binding, allowing inheritance of Arris\App class
            self::$instance = new static($config, $options, $services);
        }

        return self::$instance;
    }

    public function __construct(array $files = [])
    {
        parent::__construct($files);

        $this->data = self::array_merge_recursive_replace($this->getDefaults(), (new Config($files))->data);
    }

    /**
     * Аналог array_replace_recursive(), но если
     *
     * @param array $original
     * @param array $patch
     *
     * @return array
     */
    public static function array_merge_recursive_replace(array $original, array $patch): array
    {
        foreach ($patch as $key => $value) {
            if ($value === null) {
                unset($original[$key]);
            } elseif (is_array($value) && isset($original[$key]) && is_array($original[$key])) {
                $original[$key] = self::array_merge_recursive_replace($original[$key], $value);
            } else {
                $original[$key] = $value;
            }
        }

        return $original;
    }

}