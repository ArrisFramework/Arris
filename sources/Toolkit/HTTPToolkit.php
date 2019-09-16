<?php

namespace Arris\Toolkit;

class HTTPToolkit
{
    /**
     * HTTP-редирект.
     * Scheme редиректа определяется так: ENV->HTTP::REDIRECT_SCHEME > $scheme > 'http'
     *
     * @param $uri
     * @param bool $replace_prev_headers
     * @param int $code
     * @param string $scheme
     */
    public static function redirect($uri, $replace_prev_headers = false, $code = 302, $scheme = '')
    {
        $default_scheme = getenv('HTTP::REDIRECT_SCHEME') ?: $scheme ?: 'http';

        if (strstr($uri, "http://") or strstr($uri, "https://")) {
            header("Location: " . $uri, $replace_prev_headers, $code);
        } else {
            header("Location: {$default_scheme}://{$_SERVER['HTTP_HOST']}{$uri}", $replace_prev_headers, $code);
        }
        exit(0);
    }



}