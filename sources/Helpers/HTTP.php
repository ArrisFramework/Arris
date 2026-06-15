<?php

namespace Arris\Helpers;

class HTTP
{
    /**
     * Получает и декодирует JSON из тела POST-запроса.
     * Замена getJSONPayload().
     *
     * Оптимизация: кэширование результата + обработка ошибок.
     *
     * @throws \JsonException
     */
    public static function jsonPayload(): mixed
    {
        static $cached = null;
        static $parsed = false;

        if (!$parsed) {
            $raw = file_get_contents('php://input');
            $cached = $raw !== '' && $raw !== false
                ? json_decode($raw, true, 512, JSON_THROW_ON_ERROR)
                : null;
            $parsed = true;
        }

        return $cached;
    }
}