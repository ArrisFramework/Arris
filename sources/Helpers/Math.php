<?php

namespace Arris\Helpers;

use InvalidArgumentException;

class Math implements MathInterface
{
    /**
     * Ограничивает значение диапазоном [min, max] (операция clamp).
     *
     * Примеры:
     *  - clamp(5, 0, 10)   => 5
     *  - clamp(-5, 0, 10)  => 0
     *  - clamp(15, 0, 10)  => 10
     *  - clamp(5.5, 0.0, 10.0) => 5.5
     *
     * @param int|float $value Значение для ограничения
     * @param int|float $min Минимальное значение
     * @param int|float $max Максимальное значение
     * @return int|float Ограниченное значение
     * @throws InvalidArgumentException Если min > max
     */
    public static function toRange(int|float $value, int|float $min, int|float $max): int|float
    {
        if ($min > $max) {
            throw new InvalidArgumentException(
                sprintf('min (%s) cannot be greater than max (%s).', $min, $max)
            );
        }

        return max($min, min($value, $max));
    }

    /**
     * Универсальный toRange для любых сравниваемых типов.
     *
     * Примеры:
     *  - clamp(5, 0, 10)                                    => 5
     *  - clamp('b', 'a', 'z')                               => 'b'
     *  - clamp($date, $start, $end, fn($a, $b) => $a <=> $b) => $date
     *
     * @template T
     * @param T $value
     * @param T $min
     * @param T $max
     * @param callable(T, T): int|null $comparator Функция сравнения (должна возвращать -1, 0, 1)
     * @return T
     */
    public static function clamp(
        mixed $value,
        mixed $min,
        mixed $max,
        ?callable $comparator = null
    ): mixed {
        // Если компаратор не указан — используем стандартное сравнение
        $compare = $comparator ?? fn($a, $b) => $a <=> $b;

        // Если value < min, вернуть min
        if ($compare($value, $min) < 0) {
            return $min;
        }

        // Если value > max, вернуть max
        if ($compare($value, $max) > 0) {
            return $max;
        }

        return $value;
    }

}