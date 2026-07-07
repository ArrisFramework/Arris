<?php

namespace Arris\Helpers;

interface ObjectsInterface
{
    public static function is_closure(mixed $suspected_closure): bool;

    public static function propertyExistsRecursive(mixed $object, string $path, string $separator = '->'): bool;

    public static function propertyGetRecursive(mixed $object, string $path, string $separator = '->', mixed $default = null): mixed;
}
