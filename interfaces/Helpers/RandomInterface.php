<?php

namespace Arris\Helpers;

interface RandomInterface
{
    /**
     * Взвешенный случайный выбор ключа из массива.
     *
     * Ключи массива — возвращаемые значения, значения — их веса.
     * Вес может быть целым или дробным и не может быть отрицательным.
     *
     * @param array<int|string, int|float> $weights Веса: ключ => вес
     * @param mixed $fallback Значение, возвращаемое если сумма весов равна нулю
     *
     * @return int|string|mixed Ключ массива или $fallback
     * @throws \InvalidArgumentException Если массив пуст, вес отрицателен или не является числом
     */
    public static function weighted(array $weights, mixed $fallback = null): mixed;
}
