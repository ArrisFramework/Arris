<?php

use Arris\App;

$repo = App::access(['secondary' => 43]);

// var_dump($repo->get('foo'));

/**
 * @var $logger \Monolog\Logger;
 */
// $logger = $repo->get('logger');

// $logger->debug('XXXX');

// var_dump($repo->get());
