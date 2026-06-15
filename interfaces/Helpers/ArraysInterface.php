<?php

namespace Arris\Helpers;

interface ArraysInterface
{
    public static function get(array $options, ?string $key, mixed $default = null): mixed;

    public static function filter(array $input, callable $callback = null, int $flag = 0): array;

    public static function allowed(mixed $value, array $allowed, mixed $default = null, bool $strict = true): mixed;

    public static function explodeToType(
        string $string,
        string $separator = ' ',
        string|callable|array|null $typeOrCallback = null
    ): array;

    public static function filterArrayForAllowed(
        array $inputArray,
        string|int $requiredKey,
        array $allowedValues,
        mixed $defaultValue
    ): mixed;

    public static function groupDatasetByColumn(array $dataset, $column_id): array;
}