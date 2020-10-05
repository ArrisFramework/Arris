<?php

namespace Arris\Helpers;

class HTTP
{
    /**
     * Проверяет, является ли переданная строка корректным URL (http/https/ftp), включая IDN
     *
     * @param $url
     * @return false|int
     */
    public static function filter_validate_url($url)
    {
        return preg_match('#((https?|ftp)://(\S*?\.\S*?))([\s)\[\]{},;"\':<]|\.\s|$)#i', $url);
    }
    
    /**
     *
     * @return string
     */
    public function getIP_1():string
    {
        return
            isset($_SERVER['HTTP_CLIENT_IP'])
                ? $_SERVER['HTTP_CLIENT_IP']
                : (
            isset($_SERVER['HTTP_X_FORWARDED_FOR'])
                ? $_SERVER['HTTP_X_FORWARDED_FOR']
                : $_SERVER['REMOTE_ADDR']
            );
    }
    
    public function getIP_2()
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
    
}