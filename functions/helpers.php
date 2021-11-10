<?php

// Хэлперы объявлены ВНЕ неймспейса

if (version_compare(PHP_VERSION, "7.3") < 0 && !function_exists("is_countable")) {
    /**
     * @param $var
     * @return bool
     */
    function is_countable($var): bool
    {
        return (is_array($var) || is_object($var) || is_iterable($var) || $var instanceof Countable);
    }
}

if (!function_exists('d')) {
    /**
     * Dump
     */
    function d() {
        if (php_sapi_name() !== "cli") echo '<pre>';
        if (func_num_args()) {
            foreach (func_get_args() as $arg) {
                var_dump($arg);
            }
        }
        if (php_sapi_name() !== "cli") echo '</pre>';
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die
     */
    function dd() {
        if (php_sapi_name() !== "cli") echo '<pre>';
        if (func_num_args()) {
            foreach (func_get_args() as $arg) {
                var_dump($arg);
            }
        }
        if (php_sapi_name() !== "cli") echo '</pre>';

        die;
    }
}

if (!function_exists('__env')) {
    /**
     * Возвращает переменную окружения либо (если её нет) - значение по умолчанию (null)
     *
     * @param $key
     * @param null $value
     * @return array|false|string|null
     */
    function __env($key, $value = null)
    {
        return array_key_exists($key, getenv()) ? getenv($key) : $value;
    }
}

if (!function_exists('__envPath')) {
    /**
     * Возвращает значение пути (path) из окружения (env), возможно, с заключительным слэшэм
     *
     * @param $key
     * @param bool $with_tailing_slash (false)
     * @return string
     */
    function __envPath($key, $with_tailing_slash = false)
    {
        return $with_tailing_slash
            ? rtrim(getenv($key), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            : rtrim(getenv($key), DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('getJSONPayload')) {

    /**
     * Возвращает десериализованный payload
     *
     * Должно применяться для получения массива $_REQUEST для получения данных
     * отправленных с фронта через JS Fetch API методом POST JSON data
     * - во всех callback обработчиках, ожидающих данные в JSON.
     *
     * @return mixed
     */
    function getJSONPayload()
    {
        return json_decode(file_get_contents('php://input'), true);
    }
}

# -eof-
