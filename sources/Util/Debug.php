<?php

namespace Arris\Util;

class Debug implements DebugInterface
{
    /**
     * Дамп произвольного набора значений.
     *
     * Крупный минус: для CLI не печатает нормально таблицы. Это сложно, потому что нужно делать выравнивание.
     * И для вложенных таблиц выносить таблицу в отдельный блок печати.
     *
     * Table1:
     * | xxx |    yyy    |
     * | zzz | <table2>  |
     *
     * Table2:
     * ...
     *
     * @return Debug
     */
    public static function dump(): Debug
    {
        $is_not_cli = php_sapi_name() !== "cli";

        if ($is_not_cli) {
            echo '<pre>';
        }

        if (func_num_args()) {
            foreach (func_get_args() as $arg) {
                if (is_array($arg)) {
                    echo self::ddt_prepare($arg);
                } else {
                    var_dump($arg);
                }
            }
        }

        if ($is_not_cli) {
            echo '</pre>';
        }

        return new self();
    }

    public function die()
    {
        die;
    }

    /**
     * Dump
     */
    public static function d()
    {
        if (php_sapi_name() !== "cli") {
            echo '<pre>';
        }

        if (func_num_args()) {
            foreach (func_get_args() as $arg) {
                var_dump($arg);
            }
        }

        if (php_sapi_name() !== "cli") {
            echo '</pre>';
        }
    }

    /**
     * Dump and die
     *
     * @param ...$args
     * @return void
     */
    public static function dd(...$args)
    {
        if (php_sapi_name() !== "cli") {
            echo '<pre>';
        }

        if (func_num_args()) {
            foreach (func_get_args() as $arg) {
                var_dump($arg);
            }
        }

        if (php_sapi_name() !== "cli") {
            echo '</pre>';
        }
        die;
    }

    private static function ddt_prepare($array):string
    {
        $print = "<table border='1'>";

        foreach ($array as $key => $value)
        {
            $v = is_array($value) ? static::ddt_prepare($value) : $value;

            $print .= "<tr>";
            $print .= "<td>{$key}</td>";
            $print .= "<td>{$v}</td>";
            $print .= "</tr>";
        }

        $print .= "</table>";

        return $print;
    }

    /**
     * Dump as table
     *
     * @param $array
     * @return void
     */
    public static function dt($array)
    {
        $is_not_cli = php_sapi_name() !== "cli";
        if ($is_not_cli) echo '<pre>';

        echo self::ddt_prepare($array);

        if ($is_not_cli) echo '</pre>';
    }

    /**
     * Dump as table and die
     *
     * @param $array
     * @return void
     */
    public static function ddt($array)
    {
        self::dt($array);
        die;
    }

    /**
     * аналог d(), но печатает строку вызова d()
     *
     * @return void
     */
    public static function dl():void
    {
        $line = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['line'];
        echo '<pre>';
        echo "----- [At line: {$line}]:<br>";
        if (func_num_args()) {
            foreach (func_get_args() as $arg) {
                var_dump($arg);
            }
        }
        echo '-----';
        echo '</pre>';
    }
}