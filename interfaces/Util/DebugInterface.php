<?php

namespace Arris\Util;

interface DebugInterface
{
    public static function dump();

    public static function d();

    public static function dd(...$args);

    public static function dt($array);

    public static function ddt($array);

    public static function dl():void;

    public static function dumpPre(array $data, int $first_gap = 4, int $second_gap = 1): string;
}