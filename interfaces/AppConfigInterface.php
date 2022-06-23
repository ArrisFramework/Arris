<?php

namespace Arris;

use Arris\Core\Dot;

interface AppConfigInterface
{
    public static function init(Dot $instance);
    public static function get($key = null); //mixed, at PHP 8.0 - Dot|mixed
    public static function set(Dot $instance);
}