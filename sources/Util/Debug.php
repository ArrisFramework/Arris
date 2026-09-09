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
     * @param $die
     * @return void
     */
    public static function ddt($array, bool $die = true)
    {
        self::dt($array);
        if ($die) {
            die;
        }
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

    /**
     * Служебный форматтер: дамп массива в стиле var_dump, но компактно —
     * каждая строка «ключ / тип / значение», колонки выровнены пробелами.
     * Возвращает строку с <pre>.
     *
     * @param array $data
     * @param int   $first_gap   отступ между колонками «ключ» и «тип»
     * @param int   $second_gap  отступ между колонками «тип» и «значение»
     * @return string
     */
    public static function dumpPre(array $data, int $first_gap = 4, int $second_gap = 1): string
    {
        $rows = [];

        foreach ($data as $key => $value) {
            switch (true) {
                case is_null($value):
                    $type = 'NULL';
                    $raw  = 'NULL';
                    break;

                case is_bool($value):
                    $type = 'bool';
                    $raw  = $value ? 'true' : 'false';
                    break;

                case is_int($value):
                    $type = 'int';
                    $raw  = (string)$value;
                    break;

                case is_float($value):
                    $type = 'float';
                    $raw  = (string)$value;
                    break;

                case is_string($value):
                    $type = 'string(' . strlen($value) . ')';
                    $raw  = '"' . $value . '"';
                    break;

                case is_array($value):
                    $type = 'array(' . count($value) . ')';
                    $raw  = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    break;

                case is_object($value):
                    $type = 'object(' . get_class($value) . ')';
                    $raw  = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    break;

                default:
                    $type = gettype($value);
                    $raw  = (string)$value;
            }

            $rows[] = [
                'key'   =>  is_int($key) ? "[{$key}]" : "[\"{$key}\"]",
                'type'  =>  $type,
                'value' =>  $raw,
            ];
        }

        if (!$rows) {
            return '<pre></pre>';
        }

        $width_key   = max(array_map(static fn($row) => strlen($row['key']), $rows));
        $width_type  = max(array_map(static fn($row) => strlen($row['type']), $rows));

        $lines = array_map(static function ($row) use ($width_key, $width_type, $first_gap, $second_gap) {
            return str_pad($row['key'],   $width_key)  . str_repeat(' ', $first_gap)  .
                str_pad($row['type'],  $width_type) . str_repeat(' ', $second_gap) .
                $row['value'];
        }, $rows);

        return '<pre>' . htmlspecialchars(implode(PHP_EOL, $lines), ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
    }

}