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
    function setOptionEnv(array $options, string $key, $env_key = null, $default_value = ''): string
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

    /**
     * @param array $options
     * @param $key
     * @param $default_value
     * @return mixed|null
     */
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

if (!function_exists('Arris\groupDatasetByColumn')) {

    /**
     * Возвращает новый датасет, индекс для строк которого равен значению колонки строки
     * Предназначен для переформатирования PDO-ответов, полученных в режиме FETCH_ASSOC
     *
     * [ 0 => [ 'id' => 5, 'data' => 10], 1 => [ 'id' => 6, 'data' => 12] .. ]
     * При вызове с аргументами ($dataset, 'id') возвращает
     * [ 5 => [ 'id' => 5, 'data' => 10], 6 => [ 'id' => 6, 'data' => 12] .. ]
     *
     * @param array $dataset
     * @param $column_id
     * @return array
     */
    function groupDatasetByColumn(array $dataset, $column_id): array
    {
        $result = [];
        array_map(static function ($row) use (&$result, $column_id){
            $result[ $row[ $column_id ] ] = $row;
        }, $dataset);
        return $result;
    }
}

if (!function_exists('Arris\jsonize')) {

    /**
     * Конвертирует в JSON
     *
     * @param $data
     * @return false|string
     * @throws \JsonException
     */
    function jsonize($data) {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
    }
}

if (!function_exists('Arris\config')) {

    /**
     * get/set config key
     *
     * @param $key
     * @param $value
     * @return array|bool|mixed|null
     */
    function config($key = null, $value = null) {
        $app = App::factory();

        if (!is_null($value)) {
            $app->setConfig($key, $value);
            return true;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $app->setConfig($k, $v);
            }
            return true;
        }

        if (empty($key)) {
            return $app->getConfig();
        }

        return $app->getConfig($key);
    }
}

if (!function_exists('Arris\app')) {

    /**
     * @param $key
     * @param $value
     *
     * @return array|bool|mixed|null
     */
    function app($key = null, $value = null) {
        $app = App::factory();

        if (!is_null($value)) {
            $app->set($key, $value);
            return true;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $app->set($k, $v);
            }
            return true;
        }

        if (empty($key)) {
            return $app->get();
        }

        return $app->get($key);
    }
}

# -eof-
