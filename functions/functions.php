<?php
/**
 * Functions for Arris Framework
 */

interface ArrisFrameworkFunctions {
    function d($value);
    function dd($value);

    function array_fill_like_list(array &$target_array, array $indexes, array $source_array, $default_value = NULL);

    function array_search_callback(array $a, callable $callback);

    function checkAllowedValue( $value, $allowed_values_array , $invalid_value = NULL );

    function sort_array_in_given_order(array $array, array $order, $sort_key):array;

    function is_countable($var): bool;
}

if (!function_exists('array_search_callback')) {

    /**
     * array_search_callback() аналогичен array_search() , только помогает искать по неодномерному массиву.
     *
     * @param array $a
     * @param callable $callback
     * @return mixed|null
     */
    function array_search_callback(array $a, callable $callback)
    {
        foreach ($a as $item) {
            $v = \call_user_func($callback, $item);
            if ( $v === true ) return $item;
        }
        return null;
    }
}

if (!function_exists('d')) {
    /**
     * Dump and die
     * @param $value
     */
    function d($value) {
        echo '<pre>';
        var_dump($value);
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die
     * @param $value
     */
    function dd($value) {
        d($value);
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

if (!function_exists('sort_array_in_given_order')) {

    /**
     * Sort array in given order by key
     * Returns array
     *
     * @param $array - array for sort [ [ id, data...], [ id, data...], ...  ]
     * @param $order - order (as array of sortkey values) [ id1, id2, id3...]
     * @param $sort_key - sorting key (id)
     * @return mixed
     */
    function sort_array_in_given_order(array $array, array $order, $sort_key):array
    {
        usort($array, function ($home, $away) use ($order, $sort_key) {
            $pos_home = array_search($home[$sort_key], $order);
            $pos_away = array_search($away[$sort_key], $order);
            return $pos_home - $pos_away;
        });
        return $array;
    }
} // sort_array_in_given_order

if (version_compare(PHP_VERSION, "7.3") < 0 && !function_exists("is_countable")) {
    /**
     * @param $var
     * @return bool
     */
    function is_countable($var): bool
    {
        return (is_array($var) || is_object($var) || is_iterable($var) || $var instanceof Countable);
    }
}

# -eof-
