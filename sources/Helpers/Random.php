<?php

namespace Arris\Helpers;

use InvalidArgumentException;

class Random implements RandomInterface
{
    /**
     * Взвешенный случайный выбор ключа из массива.
     *
     * Примеры:
     *  - Random::weighted(['a' => 1, 'b' => 3])   => 'b' примерно в 75% случаев
     *  - Random::weighted(['a' => 0, 'b' => 1])   => всегда 'b'
     *  - Random::weighted(['a' => 0], 'none')     => 'none'
     *
     * @param array<int|string, int|float> $weights Веса: ключ => вес
     * @param mixed $fallback Значение, возвращаемое если сумма весов равна нулю
     *
     * @return int|string|mixed Ключ массива или $fallback
     * @throws InvalidArgumentException Если массив пуст, вес отрицателен или не является числом
     */
    public static function weighted(array $weights, mixed $fallback = null): mixed
    {
        if ($weights === []) {
            throw new InvalidArgumentException('Weights array cannot be empty.');
        }

        $total = 0.0;

        foreach ($weights as $key => $weight) {
            if (!is_int($weight) && !is_float($weight)) {
                throw new InvalidArgumentException(sprintf(
                    'Weight for key "%s" must be int|float, %s given.',
                    $key,
                    get_debug_type($weight)
                ));
            }

            if (is_float($weight) && !is_finite($weight)) {
                throw new InvalidArgumentException(sprintf(
                    'Weight for key "%s" must be a finite number.',
                    $key
                ));
            }

            if ($weight < 0) {
                throw new InvalidArgumentException(sprintf(
                    'Weight for key "%s" cannot be negative.',
                    $key
                ));
            }

            $total += $weight;
        }

        if ($total <= 0.0) {
            return $fallback;
        }

        // mt_rand() / (mt_getrandmax() + 1) даёт [0, 1) без выхода за границу,
        // когда mt_rand() возвращает максимум. Умножение на float-сумму
        // корректно работает и с целыми, и с дробными весами.
        $roll = (mt_rand() / (mt_getrandmax() + 1)) * $total;

        foreach ($weights as $key => $weight) {
            $roll -= $weight;

            if ($roll < 0.0) {
                return $key;
            }
        }

        return $fallback;
    }
}
