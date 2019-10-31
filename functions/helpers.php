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

# -eof-