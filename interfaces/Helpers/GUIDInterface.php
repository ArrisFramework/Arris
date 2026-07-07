<?php

namespace Arris\Helpers;

interface GUIDInterface
{
    public static function generateUuid(bool $uppercase = false): string;

    public static function GUID(): string;

    public static function generateUuidV7(): string;

    public static function isValidUuid(string $uuid): bool;
}
