<?php

namespace Arris\System;

/**
 * Class Option
 *
 * @todo: реализовать флаг $is_required для метода ->key()
 * @todo: https://en.wikipedia.org/wiki/Fluent_interface
 *
 * @package Arris
 */
class Option
{
    private static $_options;
    private static $_default;
    private static $_env;
    private static $_key;

    /**
     * Устанавливает источник (массив опций)
     *
     * @param $options
     * @return static
     */
    public static function from($options)
    {
        self::$_options = $options;
        return new static;
    }

    /**
     * Устанавливает значение по умолчанию
     *
     * @param $value
     * @return static
     */
    public static function default($value)
    {
        self::$_default = $value;
        return new static;
    }

    /**
     * Устанавливает значение по ключу в окружении
     *
     * @param $key
     * @return mixed
     */
    public static function env($key)
    {
        if (empty(self::$_options)) {
            return getenv($key);
        } else {
            self::$_env = $key;
        }

        return new static;
    }

    /**
     * FINAL: Возвращает значение опции, либо значение переменной окружения либо дефолтное значение, либо null
     *
     * @param $key
     * @return mixed
     */
    public static function key($key, bool $is_required = false)
    {
        self::$_key = $key;

        $result = null;

        if (empty(self::$_options) || is_null(self::$_key)) {
            if (is_null(self::$_env)) {
                $result = self::$_default;
            } else {
                $result = getenv(self::$_env);
            }
        } elseif (array_key_exists(self::$_key, self::$_options)) {
            $result = self::$_options[self::$_key];
        } elseif (!is_null(self::$_env)) {
            $result = getenv(self::$_env);
        } else {
            $result = self::$_default;
        }

        self::$_options = null;
        self::$_key = null;
        self::$_env = null;
        self::$_default = null;

        return $result;
    }

}