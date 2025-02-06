<?php
/**
 * Хэлперы объявлены ВНЕ неймспейса
 */

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




# -eof-
