<?php

namespace Arris;

/**
 * Class Url
 *
 * @package Arris
 */
class Url
{
    /**
     * alais of http_redirect() method
     *
     * @param $uri
     * @param int $code
     * @param string $scheme
     * @param bool $replace_headers
     */
    public static function redirect($uri, $code = 302, $scheme = '', $replace_headers = true)
    {
        if ((strpos( $uri, "http://" ) !== false || strpos( $uri, "https://" ) !== false)) {
            header("Location: {$uri}", $replace_headers, $code);
            exit(0);
        }
        
        $scheme = $scheme ?: 'http';
        $scheme = str_replace('://', '', $scheme);
    
        header("Location: {$scheme}://{$_SERVER['HTTP_HOST']}{$uri}", $replace_headers, $code);
        exit(0);
    }
    
}