<?php

namespace Arris;

use Adbar\Dot;

interface AppConfigInterface
{
    public static function init(Dot &$instance);
    public static function get(): Dot;
    public static function set(Dot &$instance);

}