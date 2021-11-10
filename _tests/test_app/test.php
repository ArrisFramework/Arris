<?php

/*function x() {
    return func_num_args();
}

function y($arg = null) {
    return func_num_args();
}

var_dump( x() );
var_dump( y() );
var_dump( y('5', 3));*/

use Arris\App;

require_once __DIR__ . '/../../vendor/autoload.php';

$repo = App::factory(['initial' => 42]);

\Arris\AppLogger::init('test', 'test');

$repo->set('logger', \Arris\AppLogger::scope('default'));

$repo->set('foo', 'bar');

require_once 'test2.php';

var_dump( $repo('foo') );




