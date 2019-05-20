<?php

if (!function_exists('dd')) {
    /**
     * Dump and die
     * @param $value
     */
    function dd($value) {
        echo '<pre>';
        var_dump($value);
        die;
    }
}

if (!function_exists('array_fill_like_list')) {
    /**
     *
     * Аналог list($dataset['a'], $dataset['b']) = explode(',', 'AAAAAA,BBBBBB'); только с учетом размерной массивов и дефолтными знач
     * Example: array_fill_like_list($dataset, ['a', 'b', 'c'], explode(',', 'AAAAAA,BBBBBB'), 'ZZZZZ' );
     *
     * @package KarelWintersky/CoreFunctions
     *
     * @param array $target_array
     * @param array $indexes
     * @param array $source_array
     * @param null $default_value
     */
    function array_fill_like_list(array &$target_array, array $indexes, array $source_array, $default_value = NULL)
    {
        foreach ($indexes as $i => $index) {
            $target_array[ $index ] = array_key_exists($i, $source_array) ? $source_array[ $i ] : $default_value;
        }
    }
}

if (!function_exists('checkAllowedValue')) {

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
        } else {
            $key = array_search( $value, $allowed_values_array);

            return ($key !== FALSE) ? $allowed_values_array[ $key ] : $invalid_value;
        }
    }

}


