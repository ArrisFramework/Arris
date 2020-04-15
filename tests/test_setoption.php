<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '../vendor/autoload.php';

use function Arris\setOptionEnv as test;

$options = [
    'key1'  =>  'key1 is 1',
    'key2'  =>  'key2 is 4'
];

putenv("FOO=BAR");

echo __LINE__, ' : ', 'key1 is 1', ' : '; var_dump( test($options, 'key1', 'FOO', __LINE__) );

echo __LINE__, ' : ', 'BAR', ' : '; var_dump( test($options, 'key3', 'FOO', __LINE__) );

echo __LINE__, ' : ', '18', ' : '; var_dump( test($options, 'key3', null, __LINE__) );

echo __LINE__, ' : ', '20', ' : '; var_dump( test($options, 'key3', 'XXX', __LINE__) );

echo __LINE__, ' : ', 'BAR', ' : '; var_dump( test([], 'key3', 'FOO', __LINE__) );

echo __LINE__, ' : ', '24', ' : '; var_dump( test([], 'key3', 'XXX', __LINE__) );

echo __LINE__, ' : ', '26', ' : '; var_dump( test([], 'key3', null, __LINE__) );

echo __LINE__, ' : ', 'BAR', ' : '; var_dump( test($options, null, 'FOO', __LINE__) );

echo __LINE__, ' : ', '30', ' : '; var_dump( test($options, null, 'XXX', __LINE__) );

echo __LINE__, ' : ', '32', ' : '; var_dump( test($options, null, null, __LINE__) );

echo __LINE__, ' : ', 'BAR', ' : '; var_dump( test([], null, 'FOO', __LINE__) );

echo __LINE__, ' : ', '36', ' : '; var_dump( test([], null, 'XXX', __LINE__) );

echo __LINE__, ' : ', '38', ' : '; var_dump( test([], null, null, __LINE__) );

echo __LINE__, ' : ', '40', ' : '; var_dump( test([ 'x' => 'y'], 'xz', 'FOO', __LINE__) );

$options = [ '__cache_key_format' => '213'];

var_dump( test($options, 'cache_key_format', 'NGINX.CACHE_KEY_FORMAT', 'GET|||HOST|PATH') );




