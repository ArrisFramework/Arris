<?php

namespace Arris\Helpers;

interface MathInterface
{
    public static function toRange(int|float $value, int|float $min, int|float $max): int|float;

    public static function clamp(mixed $value, mixed $min, mixed $max, ?callable $comparator = null): mixed;
}
