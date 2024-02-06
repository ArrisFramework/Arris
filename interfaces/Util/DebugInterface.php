<?php

namespace Arris\Util;

interface DebugInterface
{
    /**
     * Dump
     *
     * @return void
     */
    public static function d();

    /**
     * Dump and die
     *
     * @param ...$args
     * @return void
     */
    public static function dd(...$args);

    /**
     * Dump as table
     *
     * @param $array
     * @return void
     */
    public static function dt($array);

    /**
     * Dump as table and die
     *
     * @param $array
     * @return void
     */
    public static function ddt($array);

}