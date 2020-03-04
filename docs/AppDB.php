<?php


namespace Arris;


use PDO;

// DB::suffix('suffix')->config($config)->logger($logger)->options([])->init();

class AppDB
{
    public static function init()
    {

    }

    public static function prepare()
    {

    }

    public static function query()
    {

    }



    public function __construct()
    {
    }



    private static function getKey($suffix = null):string
    {
        return 'database' . ($suffix ? ":{$suffix}" : '');
    }
}

class DBC {
    private static $current_connection;

    public static function setDefaultConnection($suffix)
    {

    }

    /**
     *
     * @param $statement
     * @param array $driver_options
     * @return bool|\PDOStatement
     * @throws \Exception
     */
    public static function prepare($statement, $driver_options = [])
    {
        return DB::C(self::$current_connection)->prepare($statement, $driver_options);
    }

    /**
     * @param $statement
     * @param $mode
     * @param null $arg3
     * @param array $ctorargs
     * @return false|\PDOStatement
     * @throws \Exception
     */
    public static function query($statement, $mode = PDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = null, array $ctorargs = array())
    {
        return DB::C(self::$current_connection)->query($statement, $mode, $arg3, $ctorargs);
    }
}