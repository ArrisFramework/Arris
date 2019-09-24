<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '../vendor/autoload.php';

use Arris\Toolkit\CDNNowToolkit;

$ENV = [
    'username'      =>  '',
    'password'      =>  '',
    'project_name'  =>  '',
    'project_token' =>  '',

    'client_token'  =>  ''
];

CDNNowToolkit::init($ENV['username'], $ENV['password'], $ENV['client_token'], $ENV['project_token']);

$is_auth = CDNNowToolkit::makeAuth();

/*if ($is_auth) {
    $data = CDNNowToolkit::getStatistic(2019, 8, $ENV['project_token']);
}*/

if ($is_auth) {
    $data = CDNNowToolkit::getStatistic(2019, 8, $ENV['project_token']);

    $resp = CDNNowToolkit::clearCache([]);
    var_dump($resp);
}

