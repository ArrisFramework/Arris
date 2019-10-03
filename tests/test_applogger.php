<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '../vendor/autoload.php';

use Arris\AppLogger;
use Monolog\Logger;

try {
    AppLogger::init('application', bin2hex(random_bytes(8)),
        [
            'bubbling' => true
        ] );
    // AppLogger::addScopeX('main', [], true);

    AppLogger::addScope('mysql', [
        [ '__mysql.100-debug.log', Logger::DEBUG, ['enable' => true]],
        [ '__mysql.250-notice.log', Logger::NOTICE,  ['enable' => true]],
        [ '__mysql.300-warning.log', Logger::WARNING,  ['enable' => false]],
        [ '__mysql.400-error.log', Logger::ERROR,  ['enable' => true]],
    ]);

   /* AppLogger::addScope('mysql', [
        [ '__mysql.100-debug.log', Logger::DEBUG, FALSE ],
        [ '__mysql.250-notice.log', Logger::NOTICE, FALSE, FALSE  ],
        [ '__mysql.300-warning.log', Logger::WARNING, FALSE ],
        [ '__mysql.400-error.log', Logger::ERROR, FALSE ],
    ]);*/

    AppLogger::scope('mysql')->debug("mysql::Debug", [ ['x'], ['y']]);

    AppLogger::scope('mysql')->notice('mysql::Notice', ['x', 'y']);

    AppLogger::scope('mysql')->warn("mysql::Warning ");

    AppLogger::scope('mysql')->error('mysql::Error', ['foobar']);

    // AppLogger::scope('main')->debug('xxxxx');

    // AppLogger::scope('usage')->debug('Usage', [0, 1, 2]);

} catch (Exception $e) {
    var_dump($e->getMessage());
}


