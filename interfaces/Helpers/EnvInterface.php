<?php

namespace Arris\Helpers;

interface EnvInterface
{
    public static function get(string $key, mixed $default = null, string $type = ''): mixed;

    public static function option(array $options, ?string $key, ?string $envKey = null, string $default = ''): string;
}
