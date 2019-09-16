<?php

interface ArrisFrameworkHelpers {

    function ArrisFrameWorkSetOption(array $options, string $key, $env_key = null, $default_value = '');

}

if (!function_exists('ArrisFrameWorkSetOption')) {

    /**
     * use:
     * use function ArrisFrameWorkSetOption as setOption;
     *
     * $nginx_cache_levels = setOption($options, 'cache_levels', 'NGINX::NGINX_CACHE_LEVELS', '1:2');
     *
     * @param array $options
     * @param string $key
     * @param mixed $env_key
     * @param string $default_value
     * @return string
     */
    function ArrisFrameWorkSetOption(array $options, string $key, $env_key = null, $default_value = '')
    {
        // return (array_key_exists($key, $options) ? $options[$key] : null) ?: getenv($env_key) ?: $default_value;

        return (array_key_exists($key, $options) ? $options[$key] : null)
            ?: (!is_null($env_key) ? getenv($env_key) : null )
                ?: $default_value;
    }
}

# -eof-
