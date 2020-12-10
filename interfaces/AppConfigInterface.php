<?php

namespace Arris;

use Arris\Core\Dot;

interface AppConfigInterface
{
    public static function init(Dot &$instance);
    public static function get(): Dot;
    public static function set(Dot &$instance);

}