<?php

use Arris\App;

require_once __DIR__ . '/../../vendor/autoload.php';

$app = App::factory();

$app->setConfig(['key1' => 1, 'key2' => [ 'key2_2' => 2 ]]);

/*var_dump($app->getConfig()->toJson());
var_dump($app->getConfig('key1'));
var_dump($app->getConfig('key2.key2_2'));*/

$app->{'XXX.YYY'} = 55;

var_dump($app->{'XXX'});
var_dump($app->{'XXX.YYY'});

$app->set('data', 'DATA');

var_dump($app->get('data'));


