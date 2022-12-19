<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '../vendor/autoload.php';

use Arris\AppLogger;
use Monolog\Handler\RedisHandler;
use Monolog\Logger;

try {
    AppLogger::init('application', bin2hex(random_bytes(8)),
        [
            'default_logfile_path'  =>  __DIR__ ,
            'bubbling' => false,
            'add_scope_to_log' => true,
            'deferred_scope_separate_files' => true
        ] );

    AppLogger::addScope('main', [], true);

    AppLogger::addScope('mysql', [
        [ '__mysql.100-debug.log', Logger::DEBUG, 'enable' => true],
        [ '__mysql.250-notice.log', Logger::NOTICE,  'enable' => true],
        [ '__mysql.300-warning.log', Logger::WARNING,  'enable' => true],
        [ '__mysql.400-error.log', Logger::ERROR,  'enable' => true],
    ]);

    $redis = new Predis\Client();
    $redis->connect();

    AppLogger::addScope('test', [
        [  'debug.log', Logger::DEBUG, 'handler' => \Psr\Log\NullLogger::class ],
        [  'monolog', Logger::ERROR, 'handler' => new \Monolog\Handler\RedisHandler( $redis, 'monolog', Logger::ERROR ), 'bubbling' => false ],
        [ 'monolog', Logger::ERROR, 'handler' => [ RedisHandler::class, $redis ] ]
    ]);

    AppLogger::scope('test')->debug('Debug');
    AppLogger::scope('test')->error('Error');

    /*AppLogger::scope('mysql')->debug("mysql::Debug", [ ['x'], ['y']]);

    AppLogger::scope('mysql')->notice('mysql::Notice', ['x', 'y']);

    AppLogger::scope('mysql')->warning("mysql::Warning ");

    AppLogger::scope('mysql')->error('mysql::Error', ['foobar']);

    AppLogger::scope('main')->debug('xxxxx');

    AppLogger::scope('usage')->debug('Usage', [0, 1, 2]);

    AppLogger::scope('mysql')->emergency('MYSQL EMERGENCY');

    AppLogger::scope('usage')->emergency('EMERGENCY USAGE');*/



} catch (Exception $e) {
    var_dump($e->getMessage());
}


