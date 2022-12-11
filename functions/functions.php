<?php

namespace Arris;

if (!function_exists('Arris\checkAllowedValue')) {

    /**
     * @param $value
     * @param $allowed_values_array
     * @param null $invalid_value
     * @return mixed|null
     */
    function checkAllowedValue( $value, $allowed_values_array , $invalid_value = NULL )
    {
        if (empty($value)) {
            return $invalid_value;
        }
    
        $key = array_search( $value, $allowed_values_array);
    
        return ($key !== FALSE) ? $allowed_values_array[ $key ] : $invalid_value;
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
     * @param string $key
     * @param mixed $env_key
     * @param string $default_value
     * @return string
     */
    function setOptionEnv(array $options, $key, $env_key = null, $default_value = '')
    {
        if (empty($options) || is_null($key) || !array_key_exists($key, $options)) {
            if (is_null($env_key)) {
                return $default_value;
            }
            if (getenv($env_key) === false) {
                return $default_value;
            }
            return getenv($env_key);
        }
    
        return $options[$key];
    }
}

if (!function_exists('Arris\setOption')) {
    function setOption(array $options = [], $key = null, $default_value = null)
    {
        if (!is_array($options)) {
            return $default_value;
        }

        if (is_null($key)) {
            return $default_value;
        }

        return array_key_exists($key, $options) ? $options[ $key ] : $default_value;
    }
}

/*if (! function_exists('app')) {

    function app($options = null, array $parameters = [])
    {
        if (is_null($options)) {
            return App::factory($options);
        }

        return Container::getInstance()->make($abstract, $parameters);
    }
}*/



# -eof-
