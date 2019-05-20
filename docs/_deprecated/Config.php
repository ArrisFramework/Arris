<?php
/**
 * User: Karel Wintersky
 * Date: 08.08.2018, time: 21:25
 */

namespace Arris;

/**
 * Simple config-class.
 * Простой Config-класс для упрощения доступа к конфигу.
 * Базируется на ArrisFramework/Config class
 *
 * Required initialization like:
 *
 *      Config::init( include  'config/config.php' );
 * or
 *      Config::init(['../config/config.php']);
 * or
 *      Config::init(['../config/config.php', '../config2/config2.php']);
 *
 */
class Config {
    const VERSION = '1.5/ArrisFramework';

    const GLUE = '/';
    private static $config = [];

    public static function init_once($data)
    {
        self::$config = $data;
    }

    /**
     * @param $configs_set
     */
    public static function init($configs_set)
    {
        if (is_array($configs_set)) {
            foreach ($configs_set as $config_key => $config_file) {
                $config_subpath = (is_int($config_key) || ($config_key == '/') || ($config_key == '')) ? '' : $config_key;

                // здесь в версии 1.4 я добавляю к пути __DIR__ (что эквивалентно dirname(__FILE__) ).
                self::config_append( __DIR__ . DIRECTORY_SEPARATOR . $config_file , $config_subpath );
            }
        } elseif (is_string($configs_set)) {
            self::config_append( $configs_set );
        }

        if (empty(self::$config)) {
            die('Config is empty or files not found. Check every file from set [ <code> ' . implode(' ', $configs_set) . ' </code> ]');
            // хотя правильно тут кидать эксепшен
        }

    }

    /**
     * @param $parents
     * @param null $default_value
     * @return array|mixed|null
     */
    public static function get($parents, $default_value = null)
    {
        if ($parents === '') {
            return $default_value;
        }

        if (!is_array($parents)) {
            $parents = explode(self::GLUE, $parents);
        }

        $ref = &self::$config;

        foreach ((array) $parents as $parent) {
            if (is_array($ref) && array_key_exists($parent, $ref)) {
                $ref = &$ref[$parent];
            } else {
                return $default_value;
            }
        }
        return $ref;

    }

    /**
     * @param $parents
     * @param $value
     * @return bool
     */
    public static function set($parents, $value)
    {
        if (!is_array($parents)) {
            $parents = explode(self::GLUE, (string) $parents);
        }

        if (empty($parents)) return false;

        $ref = &self::$config;

        foreach ($parents as $parent) {
            if (isset($ref) && !is_array($ref)) {
                $ref = array();
            }

            $ref = &$ref[$parent];
        }

        $ref = $value;
        return true;
    }

    public static function d()
    {
        echo '<pre>';
        print_r(self::$config);
    }

    public static function dd()
    {
        self::d();
        die;
    }

    /**
     *
     * @param $file
     * @param string $subpath
     */
    public static function config_append($file, $subpath = '')
    {
        $new_config = include $file;

        if ($subpath == "" || $subpath == self::GLUE) {

            foreach ($new_config as $key => $part) {

                if (array_key_exists($key, self::$config)) {
                    self::$config[$key] = array_merge(self::$config[$key], $part);
                } else {
                    self::$config[$key] = $part;
                }
            }

        } else {
            self::$config["{$subpath}"] = $new_config;
        }

        unset($new_config);
    }

}
