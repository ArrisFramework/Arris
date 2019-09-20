<?php
/**
 * User: Karel Wintersky
 *
 * Class Core
 * Namespace: Arris
 *
 * Date: 28.02.2018, time: 4:25
 */

namespace Arris;

/**
 * Здесь собраны функции, не вошедшие в остальные классы. Как правило, это функции общего назначения или функции,
 * еще не сгруппированные в отдельные модули
 *
 * Class Core
 * @package Arris
 */
class Core
{
    const VERSION = '1.3';

    /**
     *
     * Аналог list($dataset['a'], $dataset['b']) = explode(',', 'AAAAAA,BBBBBB');
     * только с учетом размерности массивов и с дефолтными значениями
     * Example: array_fill_like_list($dataset, ['a', 'b', 'c'], explode(',', 'AAAAAA,BBBBBB'), 'ZZZZZ' );
     *
     * @package KarelWintersky/CoreFunctions
     *
     * @param array $target_array
     * @param array $indexes
     * @param array $source_array
     * @param null $default_value
     */
    public static function array_fill_like_list(array &$target_array, array $indexes, array $source_array, $default_value = NULL)
    {
        foreach ($indexes as $i => $index) {
            $target_array[ $index ] = array_key_exists($i, $source_array) ? $source_array[ $i ] : $default_value;
        }
    }

    public static function getClientIp()
    {
        if (getenv('HTTP_CLIENT_IP')) {
            $ipAddress = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ipAddress = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_X_FORWARDED')) {
            $ipAddress = getenv('HTTP_X_FORWARDED');
        } elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ipAddress = getenv('HTTP_FORWARDED_FOR');
        } elseif (getenv('HTTP_FORWARDED')) {
            $ipAddress = getenv('HTTP_FORWARDED');
        } elseif (getenv('REMOTE_ADDR')) {
            $ipAddress = getenv('REMOTE_ADDR');
        } else {
            $ipAddress = '127.0.0.1';
        }

        return $ipAddress;
    }


    public static function getIP()
    {
        if (php_sapi_name() === 'cli') return '127.0.0.1';

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

    public static function GUID()
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }

        if (function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
            return strtoupper( vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)) );
        }

        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }


    public static function random_int($min, $max) {
        return (function_exists('random_int') === true) ? random_int($min, $max) : mt_rand($min, $max);
    }

    public static function rmdir_tree($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::rmdir_tree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }



}