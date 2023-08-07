<?php

/**
 * @method static go
 */

use Arris\Util\Timer;

require_once __DIR__ . '/../../vendor/autoload.php';

Timer::init('test', 6);
Timer::start();
sleep( random_int(1, 5) );
var_dump( Timer::stop() );

$t = new Timer();
$t->go();
sleep( random_int(1, 5) );
var_dump( $t->stop() );



