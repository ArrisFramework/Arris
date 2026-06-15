<?php

namespace Arris;

if (!function_exists('Arris\checkAllowedValue')) {

    /**
     * @param $value
     * @param $allowed_values_array
     * @param null $invalid_value
     * @return mixed|null
     *
     * @deprecated Use \Arris\Helpers\Arrays::allowed() instead
     */
    function checkAllowedValue($value, $allowed_values_array , $invalid_value = null): mixed
    {
        return \Arris\Helpers\Arrays::allowed($value, $allowed_values_array, $invalid_value);
    }
}

if (!function_exists('Arris\setOptionEnv')) {

    /**
     * use function ArrisFunctions\setOption as setOption;
     *
     * ($options, $key, $env_key, $default) =>  $options[ $key ]
     * ([], $key, $env_key, $default)       =>  get_env( $env_key )
     * ($arr, null, $env_key, $default)     =>  get_env( $env_key )
     * ([], null, null, $default)           =>  default
     * ([], null, null, null)               =>  null
     *
     * @param array $options
     * @param string|null $key
     * @param mixed|null $env_key
     * @param string $default_value
     *
     * @return string
     * @deprecated Use \Arris\Helpers\Env::option() instead
     */
    function setOptionEnv(array $options, ?string $key, mixed $env_key = null, string $default_value = ''): string
    {
        return \Arris\Helpers\Env::option($options, $key, $env_key, $default_value);
    }
}

if (!function_exists('Arris\setOption')) {
    /**
     * @param array $options
     * @param $key
     * @param $default_value
     * @return mixed|null
     *
     * @deprecated Use \Arris\Helpers\Arrays::get() instead
     */
    function setOption(array $options = [], $key = null, $default_value = null): mixed
    {
        return \Arris\Helpers\Arrays::get($options, $key, $default_value);
    }
}

