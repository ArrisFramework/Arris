<?php

declare(strict_types=1);

namespace Arris\Util;

interface RequestInterface
{
    public static function str(
        string $field,
        int $maxLength = 0,
        string $default = '',
        bool $trim = true,
        bool $allowEmpty = true,
        ?array $from = null
    ): string;

    public static function string(
        string $field,
        int $maxLength = 0,
        string $default = '',
        bool $trim = true,
        bool $allowEmpty = true,
        ?array $from = null
    ): string;

    public static function email(string $field, string $default = '', ?array $from = null): string;

    public static function int(
        string $field,
        ?int $min = null,
        ?int $max = null,
        int $default = 0,
        ?array $from = null
    ): int;

    public static function bool(string $field, bool $default = false, ?array $from = null): bool;

    public static function checkbox(string $field, bool $default = false, ?array $from = null): bool;

    public static function array(
        string $field,
        array $default = [],
        int $maxLength = 0,
        ?array $from = null
    ): array;

    public static function arr(
        string $field,
        array $default = [],
        int $maxLength = 0,
        bool $transposeMatrix = false,
        ?array $from = null
    ): array;

    public static function url(string $field, string $default = '', ?array $from = null): string;

    public static function text(string $field, bool $allowHtml = false, ?array $from = null, bool $noEmptyContent = true): string;

    public static function transposeMatrix(array $data): array;
}
