<?php

namespace Arris\Helpers;

class SizeFormatter implements SizeFormatterInterface
{
    /**
     * Форматирует размер в человекочитаемый вид.
     *
     * Примеры:
     *  - sizeFormat(1024)                        => '1 KB' (SI)
     *  - sizeFormat(1024, binary: true)          => '1 KiB' (binary)
     *  - sizeFormat(1536, 2)                     => '1.54 KB'
     *  - sizeFormat(1073741824, binary: true)    => '1 GiB'
     *
     * @param int $size Размер в байтах (может быть отрицательным)
     * @param int $decimals Количество знаков после запятой
     * @param string $decimalSeparator Разделитель десятичной части
     * @param string $thousandsSeparator Разделитель тысяч
     * @param bool $binary Использовать binary units (1024) вместо SI (1000)
     * @return string Отформатированный размер с единицей измерения
     */
    public static function sizeFormat(
        int $size,
        int $decimals = 0,
        string $decimalSeparator = '.',
        string $thousandsSeparator = ',',
        bool $binary = false
    ): string {
        // Быстрая обработка нуля
        if ($size === 0) {
            return '0 B';
        }

        $base = $binary ? 1024 : 1000;
        $units = $binary
            ? ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB']
            : ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        $absSize = abs($size);

        // Оптимизация: используем log вместо strlen(strval())
        // Это быстрее и корректнее работает с отрицательными числами
        $index = (int) floor(log($absSize, $base));
        $index = min($index, count($units) - 1);

        $number = number_format(
            $size / ($base ** $index),
            $decimals,
            $decimalSeparator,
            $thousandsSeparator
        );

        return "{$number} {$units[$index]}";
    }

    /**
     * Альтернативная реализация с циклом (более читаемая, но чуть медленнее).
     * Полезна для понимания логики.
     */
    public static function sizeFormatLoop(
        int $size,
        int $decimals = 0,
        string $decimalSeparator = '.',
        string $thousandsSeparator = ',',
        bool $binary = false
    ): string {
        if ($size === 0) {
            return '0 B';
        }

        $base = $binary ? 1024 : 1000;
        $units = $binary
            ? ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB']
            : ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        $absSize = abs($size);
        $index = 0;
        $temp = $absSize;

        // Цикл делит на базу, пока не достигнет нужной единицы
        while ($temp >= $base && $index < count($units) - 1) {
            $temp /= $base;
            $index++;
        }

        $number = number_format(
            $size / ($base ** $index),
            $decimals,
            $decimalSeparator,
            $thousandsSeparator
        );

        return "{$number} {$units[$index]}";
    }

    /**
     * Самая быстрая реализация с match (для production).
     * Жертвует гибкостью ради производительности.
     */
    public static function sizeFormatFast(
        int $size,
        int $decimals = 0,
        string $decimalSeparator = '.',
        string $thousandsSeparator = ','
    ): string {
        if ($size === 0) {
            return '0 B';
        }

        $absSize = abs($size);

        // Match с диапазонами — самый быстрый способ
        [$divisor, $unit] = match (true) {
            $absSize >= 1e24 => [1e24, 'YB'],
            $absSize >= 1e21 => [1e21, 'ZB'],
            $absSize >= 1e18 => [1e18, 'EB'],
            $absSize >= 1e15 => [1e15, 'PB'],
            $absSize >= 1e12 => [1e12, 'TB'],
            $absSize >= 1e9  => [1e9,  'GB'],
            $absSize >= 1e6  => [1e6,  'MB'],
            $absSize >= 1e3  => [1e3,  'KB'],
            default          => [1,     'B'],
        };

        $number = number_format(
            $size / $divisor,
            $decimals,
            $decimalSeparator,
            $thousandsSeparator
        );

        return "{$number} {$unit}";
    }
}