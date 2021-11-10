<?php

use Arris\App;

require_once __DIR__ . '/../../vendor/autoload.php';

$CONFIG = [];
$CONFIG['DB_CONNECTIONS'] = [
    'DATA'  => [                                                    // // $CONFIG['DB_CONNECTIONS']['DATA']
        'hostname'          =>  1,          // $CONFIG['DB_CONNECTIONS']['DATA']['hostname']
        'database'          =>  2,          // $CONFIG['DB_CONNECTIONS']['DATA']['database']
        'username'          =>  3,      // $CONFIG['DB_CONNECTIONS']['DATA']['username']
        'password'          =>  4,      // $CONFIG['DB_CONNECTIONS']['DATA']['password']
        'port'              =>  5,          // $CONFIG['DB_CONNECTIONS']['DATA']['port']
        'charset'           =>  'utf8',                             // will default utf8
        'charset_collate'   =>  'utf8_general_ci',                  // default utf8_general_ci
    ],
    'SPHINX'    =>  [                                               // $CONFIG['DB_CONNECTIONS']['SPHINX']
        'hostname'          =>  6,
        'database'          =>  NULL,
        'username'          =>  7,
        'password'          =>  8,
        'port'              =>  9,
        'charset'           =>  NULL,
        'charset_collate'   =>  NULL
    ],
    'WAITSTAFF'  => [
        'hostname'          =>  12,
        'database'          =>  11,
        'username'          =>  'waitstaff',
        'password'          =>  '123',
        'port'              =>  10,
        'charset'           =>  'utf8',
        'charset_collate'   =>  'utf8_general_ci',
    ],
];

$app = App::factory();

$app->setConfig($CONFIG);

var_dump($app->getConfig('DB_CONNECTIONS'));
var_dump($app->getConfig('DB_CONNECTIONS.DATA'));

/*$app->{'XXX.YYY'} = 55;

var_dump($app->{'XXX'});
var_dump($app->{'XXX.YYY'});

$app->set('data', 'DATA');

var_dump($app->get('data'));*/


