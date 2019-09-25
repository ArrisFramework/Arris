<?php

namespace Arris\Toolkit;

use Arris\AppLogger;
use Monolog\Logger;
use function Arris\setOption as setOption;

interface NginxToolkitInterface {

    /**
     * Init NGINX Toolkit class
     * Options:
     * - isUseCache | ENV->NGINX.NGINX_CACHE_USE   - использовать ли кэш?
     * - isLogging | ENV->NGINX.LOG_CACHE_CLEANING - логгировать ли операции очистки кэша
     * - cache_root | ENV->NGINX.NGINX_CACHE_PATH - путь до кэша nginx
     * - cache_levels | ENV->NGINX.NGINX_CACHE_LEVELS - уровни кэша
     * - cache_key_format | ENV->NGINX.NGINX_CACHE_KEY_FORMAT - определение формата ключа
     *
     * Logger: инстанс Monolog\Logger для логгирования
     *
     * @param array $options
     * @param \Monolog\Logger $logger
     * @throws \Exception
     */
    public static function init($options = [], $logger = null);

    /**
     * Очищает nginx-кэш для переданного URL
     * Логгирует всегда
     *
     * @param string $url
     * @return bool
     */
    public static function clear_nginx_cache(string $url);

    /**
     * Полная очистка КЭША NGINX
     *
     * @return bool
     */
    public static function clear_nginx_cache_entire();

    /**
     * Рекурсивно удаляет каталоги по указанному пути
     *
     * @param $directory
     * @return bool
     */
    public static function rmdir(string $directory): bool;
}

class NginxToolkit implements NginxToolkitInterface
{
    /**
     * @var mixed
     */
    private static $nginx_cache_levels;

    /**
     * @var string
     */
    private static $nginx_cache_root;

    /**
     * @var string
     */
    private static $nginx_cache_key;

    /**
     * @var Logger
     */
    private static $LOGGER = null;

    /**
     * @var bool
     */
    private static $is_logging;

    /**
     * @var mixed
     */
    private static $is_using_cache;

    public static function init($options = [], $logger = null)
    {
        if ($logger instanceof \Monolog\Logger) {
            self::$LOGGER = $logger;
        }

        self::$is_logging = setOption($options, 'isLogging', 'NGINX.LOG_CACHE_CLEANING', false);

        self::$is_using_cache = setOption($options, 'isUseCache', 'NGINX.NGINX_CACHE_USE', false);

        self::$nginx_cache_root = setOption($options, 'cache_root', 'NGINX.NGINX_CACHE_PATH');
        self::$nginx_cache_root = rtrim(self::$nginx_cache_root, DIRECTORY_SEPARATOR);
        if (empty(self::$nginx_cache_root)) {
            throw new \Exception(__METHOD__ . ' throws error, NGINX.NGINX_CACHE_PATH is empty');
        }

        self::$nginx_cache_levels = setOption($options, 'cache_levels', 'NGINX.NGINX_CACHE_LEVELS', '1:2');
        self::$nginx_cache_levels = explode(':', self::$nginx_cache_levels);

        self::$nginx_cache_key = setOption($options, 'cache_key_format', 'NGINX.NGINX_CACHE_KEY_FORMAT', 'GET|||HOST|PATH');
    }

    public static function clear_nginx_cache(string $url)
    {
        if (self::$is_using_cache == 0) {
            return false;
        }

        if ($url === "/"):
            return self::clear_nginx_cache_entire();
        endif; // endif

        $url_parts = parse_url($url);
        $url_parts['host'] = $url_parts['host'] ?? '';
        $url_parts['path'] = $url_parts['path'] ?? '';

        $cache_key = self::$nginx_cache_key;

        $cache_key = str_replace(
            ['HOST', 'PATH'],
            [$url_parts['host'], $url_parts['path']],
            $cache_key);

        $cache_filename = md5($cache_key);

        $levels = self::$nginx_cache_levels;

        $cache_filepath = self::$nginx_cache_root;

        $offset = 0;

        foreach ($levels as $i => $level) {
            $offset -= $level;
            $cache_filepath .= "/" . substr($cache_filename, $offset, $level);
        }
        $cache_filepath .= "/{$cache_filename}";

        if (file_exists($cache_filepath)) {
            if (self::$LOGGER instanceof \Monolog\Logger) {
                self::$LOGGER->debug("NGINX Cache Force Cleaner: cached data present: ", [ $cache_filepath ]);
            }

            $unlink_status = unlink($cache_filepath);

        } else {
            if (self::$LOGGER instanceof \Monolog\Logger) {
                self::$LOGGER->debug("NGINX Cache Force Cleaner: cached data not found: ", [ $cache_filepath ]);
            }

            $unlink_status = true;
        }

        if (self::$LOGGER instanceof \Monolog\Logger) {
            self::$LOGGER->debug("NGINX Cache Force Cleaner: Clear status (key/status)", [$cache_key, $unlink_status]);
        }

        return $unlink_status;
    } // -clear_nginx_cache()

    public static function clear_nginx_cache_entire()
    {
        $unlink_status = true;

        if (self::$LOGGER instanceof \Monolog\Logger) {
            self::$LOGGER->debug("NGINX Cache Force Cleaner: requested clean whole cache");
        }

        $dir_content = array_diff(scandir(self::$nginx_cache_root), ['.', '..']);

        foreach ($dir_content as $subdir) {
            if (is_dir(self::$nginx_cache_root . DIRECTORY_SEPARATOR . $subdir)) {
                $unlink_status = $unlink_status && self::rmdir(self::$nginx_cache_root . DIRECTORY_SEPARATOR . $subdir . '/');
            }
        }

        if (self::$LOGGER instanceof \Monolog\Logger) {
            self::$LOGGER->debug("NGINX Cache Force Cleaner: whole cache clean status: ", [ self::$nginx_cache_root, $unlink_status ]);
        }

        return $unlink_status;
    }

    public static function rmdir(string $directory): bool
    {
        if (!is_dir($directory)) {
            if (self::$LOGGER instanceof \Monolog\Logger) {
                self::$LOGGER->warning(__METHOD__ . ' throws warning: no such file or directory', [ $directory ]);
            }

            return false;
        }

        $files = array_diff(scandir($directory), ['.', '..']);

        foreach ($files as $file) {
            (is_dir("{$directory}/{$file}"))
                ? self::rmdir("{$directory}/{$file}")
                : unlink("{$directory}/{$file}");
        }
        return rmdir($directory);
    }
}

# -eof-
