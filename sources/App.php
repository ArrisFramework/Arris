<?php

namespace Arris;

use Adbar\Dot;

/**
 * Выполняет функцию реестра.
 *
 * Хранит инстансы модулей - DB, Smarty, PHPAuth итп
 *
 * Class App
 * @package Arris
 */
final class App
{
    protected static $registry = [];
    
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
     * @return array|Dot|bool
     */
    public static function get($key = null): Dot
    {
        if (is_null($key)) {
            return self::$registry;
        }
        
        if (array_key_exists($key, self::$registry)) {
            return self::$registry[ $key ];
        }
        
        return false;
    }
    
    public static function set($key = null, $data = null)
    {
        if (!is_null($key)) {
            self::$registry[ $key ] = $data;
        }
    }
    
}

/*App::set(DB::class, DB::C());
App::set(PHPAuth::class, new PHPAuth(DB::C(), (new PHPAuthConfig())->loadENV('_env')->getConfig() ));
$dbc = App::get(DB::class);*/
