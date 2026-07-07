<?php

namespace Arris\Helpers;

interface MiscInterface
{
    public static function getMaxUploadFilesize(): int;

    public static function get_ini_value($key): int;
}
