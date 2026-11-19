<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Arris\Helpers\Random;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(Random::class)]
class RandomTest extends TestCase
{
    // ─────────────────────────────────────────────
    //  weighted()
    // ─────────────────────────────────────────────

    #[Test]
    #[TestDox('weighted: ключ с нулевым весом не выпадает')]
    public function weightedNeverReturnsZeroWeightKey(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $this->assertSame('b', Random::weighted(['a' => 0, 'b' => 1, 'c' => 0]));
        }
    }

    #[Test]
    #[TestDox('weighted: единственный ненулевой вес возвращается всегда')]
    public function weightedReturnsOnlyNonZeroWeightKey(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $this->assertSame(42, Random::weighted([42 => 5]));
        }
    }

    #[Test]
    #[TestDox('weighted: все веса нулевые — возвращается fallback')]
    public function weightedReturnsFallbackWhenAllWeightsAreZero(): void
    {
        $this->assertSame('none', Random::weighted(['a' => 0, 'b' => 0], 'none'));
    }

    #[Test]
    #[TestDox('weighted: все веса нулевые и fallback не задан — возвращается null')]
    public function weightedReturnsNullWhenAllWeightsAreZero(): void
    {
        $this->assertNull(Random::weighted(['a' => 0, 'b' => 0]));
    }

    #[Test]
    #[TestDox('weighted: пустой массив весов — исключение')]
    public function weightedThrowsOnEmptyArray(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Random::weighted([]);
    }

    #[Test]
    #[TestDox('weighted: отрицательный вес — исключение')]
    public function weightedThrowsOnNegativeWeight(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Random::weighted(['a' => 1, 'b' => -2]);
    }

    #[Test]
    #[TestDox('weighted: нечисловой вес — исключение')]
    #[DataProvider('nonNumericWeights')]
    public function weightedThrowsOnNonNumericWeight(mixed $weight): void
    {
        $this->expectException(InvalidArgumentException::class);

        Random::weighted(['a' => $weight]);
    }

    public static function nonNumericWeights(): array
    {
        return [
            'string' => ['1'],
            'null'   => [null],
            'bool'   => [true],
            'array'  => [[1]],
            'object' => [new \stdClass()],
        ];
    }

    #[Test]
    #[TestDox('weighted: бесконечность и NaN — исключение')]
    #[DataProvider('nonFiniteWeights')]
    public function weightedThrowsOnNonFiniteWeight(float $weight): void
    {
        $this->expectException(InvalidArgumentException::class);

        Random::weighted(['a' => $weight]);
    }

    public static function nonFiniteWeights(): array
    {
        return [
            'inf'  => [INF],
            'ninf' => [-INF],
            'nan'  => [NAN],
        ];
    }

    #[Test]
    #[TestDox('weighted: дробные веса не вызывают deprecation и работают')]
    public function weightedAcceptsFloatWeights(): void
    {
        $seen = [];

        for ($i = 0; $i < 200; $i++) {
            $seen[Random::weighted(['a' => 0.5, 'b' => 1.5, 'c' => 0.0])] = true;
        }

        $this->assertArrayHasKey('a', $seen);
        $this->assertArrayHasKey('b', $seen);
        $this->assertArrayNotHasKey('c', $seen);
    }

    #[Test]
    #[TestDox('weighted: int- и string-ключи возвращаются как есть')]
    public function weightedPreservesIntAndStringKeys(): void
    {
        $seen = [];

        for ($i = 0; $i < 200; $i++) {
            $seen[Random::weighted([1 => 1, 'one' => 1])] = true;
        }

        $this->assertArrayHasKey(1, $seen);
        $this->assertArrayHasKey('one', $seen);
    }

    #[Test]
    #[TestDox('weighted: распределение соответствует весам')]
    public function weightedDistributionMatchesWeights(): void
    {
        mt_srand(1234);

        $draws = 10_000;
        $hits = 0;

        for ($i = 0; $i < $draws; $i++) {
            if (Random::weighted(['a' => 1, 'b' => 3]) === 'b') {
                $hits++;
            }
        }

        // Ожидается 75% для 'b': допуск ±3% — это ~7 сигм, тест не флакует.
        $this->assertGreaterThan(0.72, $hits / $draws);
        $this->assertLessThan(0.78, $hits / $draws);
    }

    #[Test]
    #[TestDox('weighted: крупные целые веса не ломают сумму')]
    public function weightedHandlesLargeIntWeights(): void
    {
        $result = Random::weighted(['a' => PHP_INT_MAX, 'b' => PHP_INT_MAX]);

        $this->assertContains($result, ['a', 'b']);
    }
}
