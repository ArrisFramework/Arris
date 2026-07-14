<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Arris\Helpers\Arrays;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(Arrays::class)]
class ArraysTest extends TestCase
{
    // ─────────────────────────────────────────────
    //  filter()
    // ─────────────────────────────────────────────

    #[Test]
    #[TestDox('filter: без callback удаляет falsy-значения')]
    public function filterWithoutCallbackRemovesFalsy(): void
    {
        $input = [0, 1, false, true, '', 'hello', null, [], [1]];

        $result = Arrays::filter($input);

        $this->assertSame([1, true, 'hello', [1]], array_values($result));
    }

    #[Test]
    #[TestDox('filter: с callback фильтрует по значению')]
    public function filterWithCallbackFiltersByValue(): void
    {
        $input = [1, 2, 3, 4, 5, 6];

        $result = Arrays::filter($input, fn($v) => $v % 2 === 0);

        $this->assertSame([2, 4, 6], array_values($result));
    }

    #[Test]
    #[TestDox('filter: флаг ARRAY_FILTER_USE_KEY фильтрует по ключу')]
    public function filterWithKeyFlag(): void
    {
        $input = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];

        $result = Arrays::filter(
            $input,
            fn($key) => in_array($key, ['a', 'c']),
            ARRAY_FILTER_USE_KEY
        );

        $this->assertSame(['a' => 1, 'c' => 3], $result);
    }

    #[Test]
    #[TestDox('filter: флаг ARRAY_FILTER_USE_BOTH передаёт и ключ и значение')]
    public function filterWithBothFlag(): void
    {
        $input = ['a' => 1, 'b' => 2, 'c' => 3];

        $result = Arrays::filter(
            $input,
            fn($value, $key) => $key !== 'b' && $value > 1,
            ARRAY_FILTER_USE_BOTH
        );

        $this->assertSame(['c' => 3], $result);
    }

    #[Test]
    #[TestDox('filter: пустой массив возвращает пустой массив')]
    public function filterEmptyArray(): void
    {
        $this->assertSame([], Arrays::filter([]));
    }

    // ─────────────────────────────────────────────
    //  explodeToType()
    // ─────────────────────────────────────────────

    #[Test]
    #[TestDox('explodeToType: без типа возвращает строки как есть')]
    public function explodeToTypeWithoutType(): void
    {
        $result = Arrays::explodeToType('a,b,c', ',');

        $this->assertSame(['a', 'b', 'c'], $result);
    }

    #[Test]
    #[TestDox('explodeToType: разделитель по умолчанию — пробел')]
    public function explodeToTypeDefaultSeparator(): void
    {
        $result = Arrays::explodeToType('hello world');

        $this->assertSame(['hello', 'world'], $result);
    }

    #[Test]
    #[TestDox('explodeToType: callable применяется к каждому элементу')]
    public function explodeToTypeWithCallable(): void
    {
        $result = Arrays::explodeToType(
            'a,b,c',
            ',',
            fn(string $v): string => strtoupper($v)
        );

        $this->assertSame(['A', 'B', 'C'], $result);
    }

    #[Test]
    #[TestDox('explodeToType: массив callable применяется поэлементно')]
    public function explodeToTypeWithArrayOfCallables(): void
    {
        $result = Arrays::explodeToType(
            '10,hello,true',
            ',',
            [
                fn($v) => (int) $v,
                fn($v) => strtoupper($v),
                fn($v) => (bool) $v,
            ]
        );

        $this->assertSame([10, 'HELLO', true], $result);
    }

    #[Test]
    #[TestDox('explodeToType: пустая строка возвращает массив с одним пустым элементом')]
    public function explodeToTypeEmptyString(): void
    {
        $result = Arrays::explodeToType('', ',');

        $this->assertSame([''], $result);
    }

    #[Test]
    #[TestDox('explodeToType: строка-тип int приводит к целым числам')]
    public function explodeToTypeStringInt(): void
    {
        // Dataset::castToType должен существовать; если нет — пропускаем
        if (!class_exists(\Arris\Helpers\Dataset::class)) {
            $this->markTestSkipped('Класс Dataset не найден — тест зависит от внешнего класса.');
        }

        $result = Arrays::explodeToType('1 2 3', ' ', 'int');

        $this->assertSame([1, 2, 3], $result);
    }

    #[Test]
    #[TestDox('explodeToType: строка-тип float приводит к числам с плавающей точкой')]
    public function explodeToTypeStringFloat(): void
    {
        if (!class_exists(\Arris\Helpers\Dataset::class)) {
            $this->markTestSkipped('Класс Dataset не найден.');
        }

        $result = Arrays::explodeToType('1.5,2.7', ',', 'float');

        $this->assertSame([1.5, 2.7], $result);
    }

    #[Test]
    #[TestDox('explodeToType: массив типов приводит каждый элемент к своему типу')]
    public function explodeToTypeArrayOfTypes(): void
    {
        if (!class_exists(\Arris\Helpers\Dataset::class)) {
            $this->markTestSkipped('Класс Dataset не найден.');
        }

        $result = Arrays::explodeToType(
            '42,3.14,hello',
            ',',
            ['int', 'float', 'string']
        );

        $this->assertSame([42, 3.14, 'hello'], $result);
    }

    #[Test]
    #[TestDox('explodeToType: смешанный массив — callable и строка-тип')]
    public function explodeToTypeMixedArray(): void
    {
        if (!class_exists(\Arris\Helpers\Dataset::class)) {
            $this->markTestSkipped('Класс Dataset не найден.');
        }

        $result = Arrays::explodeToType(
            '100,hello',
            ',',
            [
                'int',
                fn($v) => strtoupper($v),
            ]
        );

        $this->assertSame([100, 'HELLO'], $result);
    }

    // ─────────────────────────────────────────────
    //  filterArrayForAllowed()
    // ─────────────────────────────────────────────

    #[Test]
    #[TestDox('filterArrayForAllowed: возвращает значение, если оно в списке разрешённых')]
    public function filterArrayForAllowedReturnsValueWhenAllowed(): void
    {
        $result = Arrays::filterArrayForAllowed(
            ['status' => 'active'],
            'status',
            ['active', 'inactive'],
            'unknown'
        );

        $this->assertSame('active', $result);
    }

    #[Test]
    #[TestDox('filterArrayForAllowed: возвращает default, если значение не разрешено')]
    public function filterArrayForAllowedReturnsDefaultWhenNotAllowed(): void
    {
        $result = Arrays::filterArrayForAllowed(
            ['status' => 'banned'],
            'status',
            ['active', 'inactive'],
            'unknown'
        );

        $this->assertSame('unknown', $result);
    }

    #[Test]
    #[TestDox('filterArrayForAllowed: возвращает default, если ключ отсутствует')]
    public function filterArrayForAllowedReturnsDefaultWhenKeyMissing(): void
    {
        $result = Arrays::filterArrayForAllowed(
            [],
            'status',
            ['active', 'inactive'],
            'unknown'
        );

        $this->assertSame('unknown', $result);
    }

    #[Test]
    #[TestDox('filterArrayForAllowed: строгое сравнение — строка "1" !== int 1')]
    public function filterArrayForAllowedStrictComparison(): void
    {
        $result = Arrays::filterArrayForAllowed(
            ['id' => '1'],
            'id',
            [1],            // int 1
            'default'
        );

        $this->assertSame('default', $result);
    }

    #[Test]
    #[TestDox('filterArrayForAllowed: работает с целочисленными ключами')]
    public function filterArrayForAllowedIntKey(): void
    {
        $result = Arrays::filterArrayForAllowed(
            [0 => 'first', 1 => 'second'],
            1,
            ['second', 'third'],
            'none'
        );

        $this->assertSame('second', $result);
    }

    #[Test]
    #[TestDox('filterArrayForAllowed: default может быть null')]
    public function filterArrayForAllowedDefaultNull(): void
    {
        $result = Arrays::filterArrayForAllowed(
            ['x' => 'bad'],
            'x',
            ['good'],
            null
        );

        $this->assertNull($result);
    }

    #[Test]
    #[TestDox('filterArrayForAllowed: значение null в массиве и разрешено')]
    public function filterArrayForAllowedNullValueAllowed(): void
    {
        $result = Arrays::filterArrayForAllowed(
            ['key' => null],
            'key',
            [null, 'a', 'b'],
            'default'
        );

        $this->assertNull($result);
    }

    // ─────────────────────────────────────────────
    //  filterValueForAllowed()
    // ─────────────────────────────────────────────

    #[Test]
    #[TestDox('filterValueForAllowed: возвращает значение, если оно в списке разрешённых')]
    public function filterValueForAllowedReturnsValueWhenAllowed(): void
    {
        $result = Arrays::filterValueForAllowed('active', ['active', 'inactive'], 'unknown');

        $this->assertSame('active', $result);
    }

    #[Test]
    #[TestDox('filterValueForAllowed: возвращает default, если значение не разрешено')]
    public function filterValueForAllowedReturnsDefaultWhenNotAllowed(): void
    {
        $result = Arrays::filterValueForAllowed('banned', ['active', 'inactive'], 'unknown');

        $this->assertSame('unknown', $result);
    }

    #[Test]
    #[TestDox('filterValueForAllowed: null значение возвращает default')]
    public function filterValueForAllowedNullReturnsDefault(): void
    {
        $result = Arrays::filterValueForAllowed(null, ['a', 'b'], 'none');

        $this->assertSame('none', $result);
    }

    #[Test]
    #[TestDox('filterValueForAllowed: default может быть null')]
    public function filterValueForAllowedDefaultNull(): void
    {
        $result = Arrays::filterValueForAllowed('bad', ['good'], null);

        $this->assertNull($result);
    }

    // ─────────────────────────────────────────────
    //  groupDatasetByColumn()
    // ─────────────────────────────────────────────

    #[Test]
    #[TestDox('groupDatasetByColumn: переиндексирует по указанной колонке')]
    public function groupDatasetByColumnReindexes(): void
    {
        $dataset = [
            0 => ['id' => 5, 'data' => 10],
            1 => ['id' => 6, 'data' => 12],
            2 => ['id' => 7, 'data' => 14],
        ];

        $result = Arrays::groupDatasetByColumn($dataset, 'id');

        $this->assertSame(
            [
                5 => ['id' => 5, 'data' => 10],
                6 => ['id' => 6, 'data' => 12],
                7 => ['id' => 7, 'data' => 14],
            ],
            $result
        );
    }

    #[Test]
    #[TestDox('groupDatasetByColumn: строковый ключ колонки')]
    public function groupDatasetByColumnStringKey(): void
    {
        $dataset = [
            ['code' => 'USD', 'rate' => 1.0],
            ['code' => 'EUR', 'rate' => 0.85],
        ];

        $result = Arrays::groupDatasetByColumn($dataset, 'code');

        $this->assertArrayHasKey('USD', $result);
        $this->assertArrayHasKey('EUR', $result);
        $this->assertSame(0.85, $result['EUR']['rate']);
    }

    #[Test]
    #[TestDox('groupDatasetByColumn: пустой датасет возвращает пустой массив')]
    public function groupDatasetByColumnEmpty(): void
    {
        $result = Arrays::groupDatasetByColumn([], 'id');

        $this->assertSame([], $result);
    }

    #[Test]
    #[TestDox('groupDatasetByColumn: дубликаты — последний элемент перезаписывает предыдущий')]
    public function groupDatasetByColumnDuplicates(): void
    {
        $dataset = [
            ['id' => 1, 'name' => 'first'],
            ['id' => 1, 'name' => 'second'],
        ];

        $result = Arrays::groupDatasetByColumn($dataset, 'id');

        $this->assertCount(1, $result);
        $this->assertSame('second', $result[1]['name']);
    }

    #[Test]
    #[TestDox('groupDatasetByColumn: сохраняет все поля строки')]
    public function groupDatasetByColumnPreservesAllFields(): void
    {
        $dataset = [
            ['id' => 1, 'a' => 'alpha', 'b' => 'beta', 'c' => 'gamma'],
        ];

        $result = Arrays::groupDatasetByColumn($dataset, 'id');

        $this->assertSame(
            ['id' => 1, 'a' => 'alpha', 'b' => 'beta', 'c' => 'gamma'],
            $result[1]
        );
    }

    // ─────────────────────────────────────────────
    //  Data Providers
    // ─────────────────────────────────────────────

    public static function filterArrayForAllowedProvider(): array
    {
        return [
            'allowed string value' => [
                ['role' => 'admin'],
                'role',
                ['admin', 'moderator', 'user'],
                'guest',
                'admin',
            ],
            'not allowed returns default' => [
                ['role' => 'superadmin'],
                'role',
                ['admin', 'moderator', 'user'],
                'guest',
                'guest',
            ],
            'missing key returns default' => [
                ['name' => 'John'],
                'role',
                ['admin'],
                'guest',
                'guest',
            ],
            'strict type mismatch' => [
                ['count' => '5'],
                'count',
                [5],
                0,
                0,
            ],
            'boolean strict comparison' => [
                ['active' => 1],
                'active',
                [true],
                false,
                false,
            ],
        ];
    }

    #[Test]
    #[DataProvider('filterArrayForAllowedProvider')]
    #[TestDox('filterArrayForAllowed (data provider): #{"name"}')]
    public function filterArrayForAllowedFromProvider(
        array $input,
        string|int $key,
        array $allowed,
        mixed $default,
        mixed $expected
    ): void {
        $this->assertSame(
            $expected,
            Arrays::filterArrayForAllowed($input, $key, $allowed, $default)
        );
    }

    public static function groupDatasetByColumnProvider(): array
    {
        return [
            'simple reindex' => [
                [['pk' => 'a', 'v' => 1], ['pk' => 'b', 'v' => 2]],
                'pk',
                ['a' => ['pk' => 'a', 'v' => 1], 'b' => ['pk' => 'b', 'v' => 2]],
            ],
            'empty dataset' => [
                [],
                'id',
                [],
            ],
            'single row' => [
                [['id' => 99, 'x' => 'y']],
                'id',
                [99 => ['id' => 99, 'x' => 'y']],
            ],
        ];
    }

    #[Test]
    #[DataProvider('groupDatasetByColumnProvider')]
    #[TestDox('groupDatasetByColumn (data provider)')]
    public function groupDatasetByColumnFromProvider(
        array $dataset,
        string $column,
        array $expected
    ): void {
        $this->assertSame(
            $expected,
            Arrays::groupDatasetByColumn($dataset, $column)
        );
    }
}