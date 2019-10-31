<?php

namespace Arris;

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