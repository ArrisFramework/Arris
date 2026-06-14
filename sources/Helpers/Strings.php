<?php

namespace Arris\Helpers;

use InvalidArgumentException;

class Strings implements StringsInterface
{
    /**
     * Преобразует строку с суффиксом размера (K, Kb, M, Mb, G, Gb) в байты.
     * Поддерживает дробные значения и пробелы (например, "1.5 Gb").
     *
     * @param string|int|float $val Значение для преобразования
     * @return int Количество байт
     */
    public static function returnBytes(string|int|float $val): int
    {
        $val = trim((string) $val);

        if ($val === '') {
            return 0;
        }

        // Регулярное выражение:
        // 1. ^\s*            : начало строки, возможные пробелы
        // 2. ([0-9]+(?:\.[0-9]+)?) : Группа 1: число (целое или дробное, например "10" или "1.5")
        // 3. \s*             : возможные пробелы между числом и суффиксом
        // 4. ([kmg]b?)?      : Группа 2 (опционально): k, m или g, за которыми опционально следует 'b' (регистронезависимо)
        // 5. \s*$            : возможные пробелы в конце и конец строки
        if (preg_match('/^\s*([0-9]+(?:\.[0-9]+)?)\s*([kmgt]b?)?\s*$/i', $val, $matches)) {
            $numericValue = (float) $matches[1];
            $suffix = strtolower($matches[2] ?? '');

            // Берем только первую букву суффикса ('k', 'm' или 'g'), игнорируя 'b'
            $baseSuffix = $suffix[0] ?? '';

            return match ($baseSuffix) {
                't' => (int) ($numericValue * (1024 ** 4)), //
                'g' => (int) ($numericValue * (1024 ** 3)), // 1 073 741 824
                'm' => (int) ($numericValue * (1024 ** 2)), // 1 048 576
                'k' => (int) ($numericValue * 1024),        // 1 024
                default => (int) $numericValue,             // Если суффикса нет или он нераспознан, считаем как байты
            };
        }

        // Если строка не соответствует формату (например, "abc" или "10tb"), возвращаем 0 или выбрасываем исключение
        return 0;
    }

    /**
     * Возвращает форму множественного числа для русского языка.
     *
     * Примеры:
     *  - pluralForm(1, 'файл|файла|файлов')   => 'файл'
     *  - pluralForm(2, 'файл|файла|файлов')   => 'файла'
     *  - pluralForm(5, 'файл|файла|файлов')   => 'файлов'
     *  - pluralForm(21, ['яблоко', 'яблока', 'яблок']) => 'яблоко'
     *
     * @param int $number Число для склонения
     * @param array<string>|string $forms Массив из 3 форм или строка с разделителем
     * @param string $glue Разделитель для строкового формата
     * @return string Правильная форма слова
     * @throws InvalidArgumentException Если передано меньше 1 формы
     */
    public static function pluralForm(int $number, array|string $forms, string $glue = '|'): string
    {
        if (is_string($forms)) {
            $forms = explode($glue, $forms);
        }

        $count = count($forms);
        if ($count === 0) {
            throw new InvalidArgumentException('Forms array cannot be empty.');
        }

        // 2. Дополняем массив до 3 элементов, если передано меньше
        // Для русского языка всегда нужно 3 формы: 1, 2, 5
        if ($count === 1) {
            $forms = [$forms[0], $forms[0], $forms[0]];
        } elseif ($count === 2) {
            $forms[] = $forms[1]; // Если 2 формы, считаем что 2-я форма для "5"
        }

        // 3. Берем абсолютное значение для корректной работы с отрицательными числами
        $absNumber = abs($number);
        $mod10 = $absNumber % 10;
        $mod100 = $absNumber % 100;

        // 4. Определяем индекс формы с использованием match (PHP 8.0+)
        // Это намного читаемее вложенных тернарных операторов
        $index = match (true) {
            // 1, 21, 31, 101... (1 файл)
            $mod10 === 1 && $mod100 !== 11                                      => 0,

            // 2-4, 22-24, 32-34... (2 файла)
            $mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)       => 1,

            // 0, 5-20, 25-30, 100-114... (5 файлов)
            default                                                             => 2,
        };

        return $forms[$index];
    }

}