<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '../vendor/autoload.php';

use Arris\Option;

putenv("zzzz=BAR");

$array = [ 1, 2, 3, 'key' => 5, 'foo' => 66];

var_dump( Option::env('zzzz') );

var_dump( Option::from($array)->key('key'));

var_dump( Option::from($array)->default(777)->key('zzz'));

