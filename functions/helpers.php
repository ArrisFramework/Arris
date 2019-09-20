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
     * Dump and die
     * @param $value
     */
    function d($value) {
        echo '<pre>';
        var_dump($value);
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die
     * @param $value
     */
    function dd($value) {
        d($value);
        die;
    }
}

# -eof-