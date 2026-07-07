<?php

namespace Arris\Helpers;

class HTTP implements HTTPInterface
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

    /**
     * Единая функция для получения reason phrase по коду
     *
     * @param int $code
     *
     * @return string
     */
    public static function getHttpReasonPhrase(int $code): string
    {
        return HTTPStatus::tryFrom($code)?->getReasonPhrase()
            ?? HTTPStatusExtended::tryFrom($code)?->getReasonPhrase()
            ?? 'Unknown Status';
    }

    /**
     * Проверка: является ли код стандартным или расширенным
     *
     * @param int $code
     *
     * @return bool
     */
    public static function isStandardHttpStatus(int $code): bool
    {
        return HTTPStatus::tryFrom($code) !== null;
    }
}