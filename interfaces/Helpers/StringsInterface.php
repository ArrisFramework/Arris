<?php

namespace Arris\Helpers;

interface StringsInterface
{

    public static function returnBytes(string|int|float $val): int;


    public static function pluralForm(int $number, array|string $forms, string $glue = '|'): string;


    public static function returnSeconds(string|int|float $val): int;

}

