<?php
/**
 * Хэлперы объявлены ВНЕ неймспейса
 */

if (!function_exists('d')) {
    /**
     * Dump
     */
    function d() {
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
}

if (!function_exists('dd')) {
    /**
     * Dump and die
     */
    function dd(...$args) {
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
        // d($args);

        die;
    }
}

if (!function_exists('_env')) {
    /**
     * Get environment variable and set type.
     *
     * @param string $key
     * @param $default
     * @param string $type (allowed: '', bool, int, float, string, array?, null)
     * @return array|mixed|string
     */
    function _env(string $key, $default, string $type = '') {
        $k = getenv($key);
        if ($k === false) {
            return $default;
        }

        if ($type !== '') {
            if ($type === 'array') {
                return explode(' ', trim(str_replace(['[', ']'], '', $k)));
            }

            $st = settype($k, $type);

            if ($st === false) {
                return $default;
            }
        }
        return $k;
    }
}

if (!function_exists('getJSONPayload')) {
    /**
     * Возвращает десериализованный payload
     *
     * Должно применяться для получения массива $_REQUEST для получения данных
     * отправленных с фронта через JS Fetch API методом POST JSON data
     * - во всех callback обработчиках, ожидающих данные в JSON.
     *
     * @return mixed
     */
    function getJSONPayload()
    {
        return json_decode(file_get_contents('php://input'), true);
    }
}

if (!function_exists('ddt')) {
    function ddt($array)
    {
        function ddt_print($array):string
        {
            $print = "<table border='1'>";

            foreach ($array as $key => $value)
            {
                $v = is_array($value) ? ddt_print($value) : $value;

                $print .= "<tr>";
                $print .= "<td>{$key}</td>";
                $print .= "<td>{$v}</td>";
                $print .= "</tr>";
            }

            $print .= "</table>";

            return $print;
        }

        $is_not_cli = php_sapi_name() !== "cli";
        if ($is_not_cli) echo '<pre>';

        echo ddt_print($array);

        if ($is_not_cli) echo '</pre>';

        die;
    }
}

if (!function_exists('dl')) {
    /**
     * аналог d(), но печатает строку вызова d()
     *
     * @return void
     */
    function dl()
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






# -eof-
