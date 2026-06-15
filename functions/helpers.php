<?php
/**
 * Хэлперы объявлены ВНЕ неймспейса
 */

if (! function_exists('dot')) {

    /**
     * Create a new Dot object with the given items
     *
     * @param  mixed  $items
     * @param bool $parse
     * @param non-empty-string $delimiter
     *
     * @return \Arris\Core\Dot<array-key, mixed>
     */
    function dot(mixed $items, bool $parse = false, string $delimiter = ".")
    {
        return new \Arris\Core\Dot($items, $parse, $delimiter);
    }
}

if (!function_exists('_env')) {

    /**
     * Get environment variable and set type.
     *
     * @param string $key
     * @param $default
     * @param string $type (allowed: '', bool, int, float, string, array?, null)
     * @return array|mixed|string
     */
    function _env(string $key, $default, string $type = ''): mixed
    {
        $k = getenv($key);
        if ($k === false) {
            return $default;
        }

        if ($type !== '') {
            if ($type === 'array') {
                return explode(' ', trim(str_replace(['[', ']'], '', $k)));
            }

            $st = settype($k, $type);

            if ($st === false) {
                return $default;
            }
        }
        return $k;
    }
}

if (!function_exists('d')) {

    /**
     * Dump
     */
    function d(...$args): void
    {
        \Arris\Util\Debug::d(...$args);
    }
}

if (!function_exists('dd')) {

    /**
     * Dump and die
     *
     * @param ...$args
     * @return void
     */
    function dd(...$args): void
    {
        \Arris\Util\Debug::dd(...$args);
    }
}

if (!function_exists('ddt')) {

    function ddt($array): void
    {
        \Arris\Util\Debug::ddt($array, true);
    }
}


if (!function_exists('dl')) {

    /**
     * Аналог d(), но печатает строку вызова d()
     *
     * @return void
     */
    function dl(): void
    {
        \Arris\Util\Debug::dl();
    }
}


# -eof- #
