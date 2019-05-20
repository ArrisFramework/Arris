<?php
/**
 * User: Karel Wintersky
 *
 * Class App
 * Namespace: Arris
 *
 * Date: 18.02.2018, time: 16:29
 */

namespace Arris;

/**
 * Class App
 *
 *
 * @package Arris
 */
class App
{
    const VERSION = '0.2';
    /**
     * Configuration keys path separator
     */
    const GLUE = '/';

    /**
     * Application root
     * @var
     */
    public static $_root;

    /**
     * Application config
     * @var
     */
    public static $config = [];

    /**
     * DB Instance (DBConnection)
     * @var
     */
    public static $dbi;

    /**
     * Monolog handler
     * @var
     */
    public static $logger;


    /**
     * Copyright value
     * @var
     */
    public static $copyright;


    /**
     * Init application states with given config files
     *
     * @param $configs_set
     * @param string $configs_dir
     */
    public static function init($configs_set, $configs_dir = '')
    {
        $cfg_dir = ($configs_dir != '')
            ? preg_replace('/^\$/', __ROOT__, $configs_dir)
            : __CONFIG__;

        if (is_array($configs_set)) {
            foreach ($configs_set as $config_key => $ini_file) {
                $config_subpath = is_int($config_key) ? '' : $config_key;
                self::config_append( $cfg_dir . $ini_file , $config_subpath );
            }
        } elseif (is_string($configs_set)) {
            self::config_append( $cfg_dir . $configs_set);
        }
    }

    /**
     * Set config key to value
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

    /**
     * Get config value by key
     *
     * @param $parents
     * @param null $default_value
     * @return null
     */
    public static function get($parents, $default_value = NULL)
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
     * Delete config value by key
     * @param $key
     */
    /**
     * @param $keypath
     */
    public static function config_unset($keypath)
    {
        self::array_unset_value(self::$config, $keypath);
    }

    /**
     * @param $file
     * @param string $subpath
     */
    public static function config_append($file, $subpath = '')
    {
        if (file_exists($file)) {
            $new_config = parse_ini_file($file, true);

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
        } else {
            $message = "FATAL ERROR: Config file `{$file}` not found. ";
            die($message);
        }
    }


    /* ========================================== */


    private static function array_unset_value(&$array, $parents)
    {
        if (!is_array($parents)) {
            $parents = explode(self::GLUE, $parents);
        }

        $key = array_shift($parents);

        if (empty($parents)) {
            unset($array[$key]);
        } else {
            self::array_unset_value($array[$key], $parents);
        }
    }
}