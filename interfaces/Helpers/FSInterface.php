<?php

namespace Arris\Helpers;

interface FSInterface
{
    public static function rmdir(string $directory, bool $preserveRoot = false, bool $followSymlinks = false): bool;

    public static function findFiles(
        string $directory,
        string|array|null $extension = null,
        string|array|null $startWith = null,
        string|array|null $endWith = null,
        bool $recursive = true
    ): array;
}
