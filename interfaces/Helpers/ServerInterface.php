<?php

namespace Arris\Helpers;

interface ServerInterface
{
    public static function getIP(): ?string;

    public static function isSSL(): bool;

    public static function redirect(string $uri, int $code = 302, bool $terminate = true): void;

    public static function createRedirectResponse(string $uri, int $code = 302): array;

    public static function isValidUrl(string $url, bool $strict = true): bool;

    public static function convertIdnToAscii(string $url): string;

    public static function rearrangeFilesPost(array $filePost, bool $filterErrors = false): array;
}
