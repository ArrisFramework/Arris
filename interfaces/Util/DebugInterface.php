<?php

namespace Arris\Util;

interface DebugInterface
{
    public static function dump();

    public static function d();

    public static function dd(...$args);

    public static function dt($array);

    public static function ddt($array);
}