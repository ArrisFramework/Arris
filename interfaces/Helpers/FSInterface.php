<?php

namespace Arris\Helpers;

interface FSInterface
{
    public static function rmdir(string $directory, bool $preserveRoot = false, bool $followSymlinks = false): bool;
}
