<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '../vendor/autoload.php';

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

function setHandler(Logger $logger, string $target, $level, bool $enabled = true)
{
    if ($enabled) {
        $logger->pushHandler(new StreamHandler($target, $level, false ));
    } else {
        $logger->pushHandler( new \Monolog\Handler\NullHandler($level) );
    }
}

$logger = new Logger('foobar');

setHandler($logger, 'logs/100.log', Logger::DEBUG);
setHandler($logger, 'logs/250.log', Logger::NOTICE, FALSE);
setHandler($logger, 'logs/300.log', Logger::WARNING);
setHandler($logger, 'logs/400.log', Logger::ERROR);

$logger->debug("Debug", [ ['x'], ['y']]);

$logger->notice('Notice', ['x', 'y']);

$logger->warn("Warning ");

$logger->error('Error', ['foobar']);


