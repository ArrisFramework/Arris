<?php

namespace Arris\Helpers;

interface SizeFormatterInterface
{
    public static function sizeFormat(int $size, int $decimals = 0, string $decimalSeparator = '.', string $thousandsSeparator = ',', bool $binary = false): string;

    public static function sizeFormatLoop(int $size, int $decimals = 0, string $decimalSeparator = '.', string $thousandsSeparator = ',', bool $binary = false): string;

    public static function sizeFormatFast(int $size, int $decimals = 0, string $decimalSeparator = '.', string $thousandsSeparator = ','): string;
}
