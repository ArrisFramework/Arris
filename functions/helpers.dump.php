<?php

if (!function_exists('d')) {

    /**
     * Dump
     */
    function d(...$args) {
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
    function dd(...$args) {
        \Arris\Util\Debug::dd(...$args);
    }
}

if (!function_exists('ddt')) {
    function ddt($array)
    {
        \Arris\Util\Debug::ddt($array);
    }
}


if (!function_exists('dl')) {
    /**
     * аналог d(), но печатает строку вызова d()
     *
     * @return void
     */
    function dl()
    {
        \Arris\Util\Debug::dl();
    }
}