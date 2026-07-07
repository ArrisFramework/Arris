<?php

namespace Arris\Helpers;

class GUID implements GUIDInterface
{
    /**
     * Генерирует UUID версии 4 (случайный) согласно RFC 4122.
     *
     * Примеры:
     *  - generateUuid()              => '550e8400-e29b-41d4-a716-446655440000'
     *  - generateUuid(uppercase: true) => '550E8400-E29B-41D4-A716-446655440000'
     *
     * @param bool $uppercase Вернуть в верхнем регистре
     *
     * @return string UUID v4 в формате 8-4-4-4-12
     * @throws \Exception
     */
    public static function generateUuid(bool $uppercase = false): string
    {
        // 16 байт криптографически стойких случайных данных
        $data = random_bytes(16);

        // Устанавливаем версию UUID (4) в байт 6
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);

        // Устанавливаем вариант UUID (RFC 4122) в байт 8
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Конвертируем в hex и форматируем
        $hex = bin2hex($data);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));

        return $uppercase ? strtoupper($uuid) : $uuid;
    }

    /**
     * Алиас для обратной совместимости со старым кодом.
     *
     * @return string UUID v4 в верхнем регистре (как в оригинале)
     * @throws \Exception
     * @deprecated Используйте generateUuid() вместо этого метода
     */
    public static function GUID(): string
    {
        return self::generateUuid(uppercase: true);
    }

    /**
     * Генерирует UUID версии 7 (time-based, сортируемый).
     * Доступен в PHP 8.2+ через random_bytes + timestamp.
     *
     * Преимущества перед v4:
     *  - Сортируемый по времени создания
     *  - Лучшая производительность для индексации в БД
     *
     * @return string UUID v7
     * @throws \Exception
     */
    public static function generateUuidV7(): string
    {
        // Timestamp в миллисекундах (48 бит)
        $time = (int) (microtime(true) * 1000);

        // Формируем 16 байт
        $data = random_bytes(16);

        // Вставляем timestamp в первые 6 байт
        $data[0] = chr(($time >> 40) & 0xff);
        $data[1] = chr(($time >> 32) & 0xff);
        $data[2] = chr(($time >> 24) & 0xff);
        $data[3] = chr(($time >> 16) & 0xff);
        $data[4] = chr(($time >> 8) & 0xff);
        $data[5] = chr($time & 0xff);

        // Устанавливаем версию (7) и вариант (RFC 4122)
        $data[6] = chr(ord($data[6]) & 0x0f | 0x70); // версия 7
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // вариант

        $hex = bin2hex($data);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }

    /**
     * Проверяет валидность UUID (любой версии).
     *
     * @param string $uuid Строка для проверки
     * @return bool true если это валидный UUID
     */
    public static function isValidUuid(string $uuid): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-7][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }
}