<?php
/**
 * Попытка собрать хэлпер-функции
 * типа https://packagist.org/packages/jbzoo/utils
 */
namespace Arris\Helpers;

class Arr
{
    /**
     *
     * @param $keys
     * @param array $array
     * @return bool
     */
    public static function arrayContainKeys($keys, array $array):bool
    {
        if (!is_array($keys)) $keys = [ $keys ];
        if (empty($array)) return false;
        if (empty($keys)) return true;

        $is_correct = true;
        foreach ($keys as $key) $is_correct = $is_correct && array_key_exists($key, $array);
        return $is_correct;
    }

    /**
     * Хелпер преобразования всех элементов массива к типу integer
     *
     * @param array $input
     * @return array
     */
    public static function map_to_integer($input): array
    {
        if (!is_array($input) || empty($input)) return [];

        return array_map(function ($i) {
            return intval($i);
        }, $input);
    }

    /**
     *
     * Аналог list($dataset['a'], $dataset['b']) = explode(',', 'AAAAAA,BBBBBB');
     * только с учетом размерности массивов и с дефолтными значениями
     *
     * Example: array_fill_like_list($dataset, ['a', 'b', 'c'], explode(',', 'AAAAAA,BBBBBB'), 'ZZZZZ' );
     *
     * @package KarelWintersky/CoreFunctions
     *
     * @param array $target_array
     * @param array $indexes
     * @param array $source_array
     * @param null $default_value
     */
    function fill_like_list(array &$target_array, array $indexes, array $source_array, $default_value = NULL)
    {
        foreach ($indexes as $i => $index) {
            $target_array[ $index ] = array_key_exists($i, $source_array) ? $source_array[ $i ] : $default_value;
        }
    }

    /**
     * array_search() с поиском по неодномерному массиву
     *
     * @param array $a
     * @param callable $callback
     * @return mixed|null
     */
    public static function search_callback(array $a, callable $callback)
    {
        foreach ($a as $item) {
            $v = \call_user_func($callback, $item);
            if ( $v === true ) return $item;
        }
        return null;
    }

    /**
     * Sort array in given order by key
     * Returns array
     *
     * @param $array - array for sort [ [ id, data...], [ id, data...], ...  ]
     * @param $order - order (as array of sortkey values) [ id1, id2, id3...]
     * @param $sort_key - sorting key (id)
     * @return mixed
     */
    public static function sort_with_order(array $array, array $order, $sort_key):array
    {
        usort($array, function ($home, $away) use ($order, $sort_key) {
            $pos_home = array_search($home[$sort_key], $order);
            $pos_away = array_search($away[$sort_key], $order);
            return $pos_home - $pos_away;
        });
        return $array;
    }


}