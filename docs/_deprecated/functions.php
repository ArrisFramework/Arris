<?php

/**
 * @package KarelWintersky/CoreFunctions
 *
 * @return null|string
 */
function getIp() {
    if (!isset ($_SERVER['REMOTE_ADDR'])) {
        return NULL;
    }

    if (array_key_exists("HTTP_X_FORWARDED_FOR", $_SERVER)) {
        $http_x_forwared_for = explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);
        $client_ip = trim(end($http_x_forwared_for));
        if (filter_var($client_ip, FILTER_VALIDATE_IP)) {
            return $client_ip;
        }
    }

    return filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) ? $_SERVER['REMOTE_ADDR'] : NULL;
}

