<?php

namespace Arris\Helpers;

interface HTTPInterface
{
    public static function jsonPayload(): mixed;

    public static function getHttpReasonPhrase(int $code): string;

    public static function isStandardHttpStatus(int $code): bool;
}
