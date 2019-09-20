<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '../vendor/autoload.php';

use Arris\AppLogger;
use Monolog\Logger;

try {
    AppLogger::init('application', bin2hex(random_bytes(8)));

    AppLogger::addScope('mysql', [
        [ '__mysql.debug-100.log', Logger::DEBUG],
        [ '__mysql.notice-250.log', Logger::NOTICE],
        [ '__mysql.warning-300.log', Logger::WARNING],
        [ '__mysql.error-400.log', Logger::ERROR],
    ]);
    AppLogger::addScope('usage', [
        [ '__usage.log' ]
    ]);

    AppLogger::scope('mysql')->warn("mysql::Warning ");

    AppLogger::scope('mysql')->error('mysql::Error', ['foobar']);

    AppLogger::scope('mysql')->notice('mysql::Notice', ['x', 'y']);

    AppLogger::scope('mysql')->debug("mysql::Debug", [ ['x'], ['y']]);

    AppLogger::scope('usage')->debug('Usage', [0, 1, 2]);

} catch (Exception $e) {
    var_dump($e->getMessage());
}


