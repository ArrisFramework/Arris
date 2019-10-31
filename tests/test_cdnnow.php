<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '../vendor/autoload.php';

use Arris\Toolkit\CDNNowToolkit;

$ENV = include '../_env.php';
$ENV = $ENV['CDNNOW'];

try {
    CDNNowToolkit::init($ENV['username'], $ENV['password'], $ENV['client_token'], $ENV['project_token']);

    $is_auth = CDNNowToolkit::makeAuth();

    /*if ($is_auth) {
        $data = CDNNowToolkit::getStatistic(2019, 8, $ENV['project_token']);
    }*/

    if ($is_auth) {
        $data = CDNNowToolkit::getStatistic(2019, 8, $ENV['project_token']);

        $resp = CDNNowToolkit::clearCache([]);
        var_dump( (new DateTime())->format('d/M/Y H:i:s') );
        var_dump($resp);
    }
} catch (Exception $e) {
    var_dump($e->getMessage());
}


