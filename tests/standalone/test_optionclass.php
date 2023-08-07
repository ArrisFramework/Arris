<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Arris\Core\Option;

putenv("zzzz=BAR");

$options = [ 'a' => 1, 'b' => 2, 'c' => 3, 'key' => 5, 'foo' => 66];

var_dump( Option::env('zzzz') );

var_dump( Option::from($options)->key('key'));

var_dump( Option::from($options)->default(777)->key('zzz'));

var_dump( Option::from($options)->default(444)->key('c'));

var_dump( Option::from($options)->key('zzz'));

var_dump( Option::from($options)->default(111)->env('zzzz')->key('bar'));
