<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Arris\AppLogger;
use Arris\DBMulti;

$ENV = include '../../_env.php';
$ENV = $ENV['DB:PGSQL'];

try {
    AppLogger::init('test', 0 );
    AppLogger::addScope('pgsql', [
        [ '_error.log', \Monolog\Logger::EMERGENCY ]
    ]);

    DBMulti::init(NULL, $ENV, AppLogger::scope('pgsql'));

DBMulti::query("INSERT INTO t1 (code, name) VALUES (1, '55555')");

    $n = DBMulti::query("SELECT * FROM t1;")->fetchAll();



    var_dump($n);

} catch (Exception $e) {
    echo 'Exception catched at global context: ', PHP_EOL, PHP_EOL;
    echo $e->getMessage(), PHP_EOL, PHP_EOL;
    echo $e->getTraceAsString();
    die;
}
