<?php

namespace Arris\Helpers;

interface StringsInterface
{
    /**
     * Преобразует строку с суффиксом размера (K, M, G, T) в байты.
     *
     * @param string|int|float $val Значение для преобразования (например, "1.5 Gb", 1024, "2M")
     * @return int Количество байт (0, если формат не распознан)
     */
    public static function returnBytes(string|int|float $val): int;

    /**
     * Возвращает правильную форму множественного числа для русского языка.
     *
     * @param int                $number Число для склонения
     * @param array<string>|string $forms  Массив из 1-3 форм или строка с разделителем
     * @param string             $glue   Разделитель для строкового формата
     * @return string Правильная форма слова
     * @throws \InvalidArgumentException Если передан пустой массив форм
     */
    public static function pluralForm(int $number, array|string $forms, string $glue = '|'): string;
}

