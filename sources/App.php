<?php

namespace Arris;

use Adbar\Dot;

/**
 * Реестр
 *
 * App::init([ options ]);
 * App::set(key, value);
 * App::get(key, default);
 *
 * Class App
 * @package Arris
 */
final class App
{
    /**
     * @var Dot
     */
    protected static $registry = null;
    
    /**
     *
     * @param array $items
     * @return Dot
     */
    public static function init($items = [])
    {
        self::$registry = new Dot($items);
        return self::$registry;
    }
    
    /**
     *
     * @param null $key
     * @return mixed
     */
    public static function get($key = null, $default = null)
    {
        if (is_null($key)) {
            return self::$registry;
        }
        
        return self::$registry->get($key, $default);
    }
    
    /**
     * @param null $key
     * @param null $data
     *
     * @return bool
     */
    public static function set($key = null, $data = null)
    {
        if (is_null(self::$registry)) {
            self::$registry = new Dot();
        }
        
        if (!is_null($key)) {
            self::$registry->set($key, $data);
            return true;
        }
        
        return false;
    }
    
}

# -eof-
