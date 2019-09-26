<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '../vendor/autoload.php';

use function Arris\setOption as test;

$options = [
    'key1'  =>  'key1 is 1',
    'key2'  =>  'key2 is 4'
];

putenv("FOO=BAR");

// 'key1 is 1'
echo __LINE__, ' : '; var_dump( test($options, 'key1', 'FOO', __LINE__) );

// BAR
echo __LINE__, ' : '; var_dump( test($options, 'key3', 'FOO', __LINE__) );

// 22
echo __LINE__, ' : '; var_dump( test($options, 'key3', null, __LINE__) );

// false (because getenv return false)
echo __LINE__, ' : '; var_dump( test($options, 'key3', 'XXX', __LINE__) );

// BAR
echo __LINE__, ' : '; var_dump( test([], 'key3', 'FOO', __LINE__) );

// false
echo __LINE__, ' : '; var_dump( test([], 'key3', 'XXX', __LINE__) );

// 34
echo __LINE__, ' : '; var_dump( test([], 'key3', null, __LINE__) );

// BAR
echo __LINE__, ' : '; var_dump( test($options, null, 'FOO', __LINE__) );

// false
echo __LINE__, ' : '; var_dump( test($options, null, 'XXX', __LINE__) );

// 43
echo __LINE__, ' : '; var_dump( test($options, null, null, __LINE__) );

// BAR
echo __LINE__, ' : '; var_dump( test([], null, 'FOO', __LINE__) );

// false
echo __LINE__, ' : '; var_dump( test([], null, 'XXX', __LINE__) );

// 52
echo __LINE__, ' : '; var_dump( test([], null, null, __LINE__) );

