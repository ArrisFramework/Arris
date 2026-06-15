<?php

namespace Arris\Helpers;

interface DatasetInterface
{
    public static function jsonize(mixed $data, int $flags = JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR):string;

    public static function map(
        array $source,
        array $rules,
        mixed $defaultUndefinedValue = null
    ): array;

    public static function castToType(mixed $value, string $type): mixed;

}