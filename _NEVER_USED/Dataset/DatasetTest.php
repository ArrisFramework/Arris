<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Arris\Helpers\Dataset;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Dataset::class)]
final class DatasetTest extends TestCase
{
    // ==========================================
    // Базовое копирование
    // ==========================================

    #[Test]
    public function testSimpleCopy(): void
    {
        $source = ['name' => 'John', 'age' => 25];
        $rules = [
            'name' => [],
            'age' => [],
        ];

        $result = Dataset::map($source, $rules);

        $this->assertSame(['name' => 'John', 'age' => 25], $result);
    }

    #[Test]
    public function testCopyWithEmptyRule(): void
    {
        $source = ['id' => 1, 'status' => 'active'];
        $rules = ['id' => [], 'status' => []];

        $result = Dataset::map($source, $rules);

        $this->assertSame(['id' => 1, 'status' => 'active'], $result);
    }

    // ==========================================
    // Переименование ключей (source/target)
    // ==========================================

    #[DataProvider('provideRenameCases')]
    #[Test]
    public function testKeyRenaming(array $source, array $rules, array $expected): void
    {
        $result = Dataset::map($source, $rules);
        $this->assertSame($expected, $result);
    }

    public static function provideRenameCases(): array
    {
        return [
            'Source only' => [
                ['user_name' => 'Jane'],
                ['username' => ['source' => 'user_name']],
                ['username' => 'Jane'],
            ],
            'Target only' => [
                ['name' => 'John'],
                ['full_name' => ['target' => 'full_name']],
                ['full_name' => 'John'],
            ],
            'Source and Target' => [
                ['old_key' => 'value'],
                ['new_key' => ['source' => 'old_key', 'target' => 'new_key']],
                ['new_key' => 'value'],
            ],
        ];
    }

    // ==========================================
    // Processor (callable и фиксированное значение)
    // ==========================================

    #[Test]
    public function testProcessorWithClosure(): void
    {
        $source = ['price' => 100, 'quantity' => 3];
        $rules = [
            'total' => [
                'processor' => fn($value, $src) => $src['price'] * $src['quantity'],
            ],
        ];

        $result = Dataset::map($source, $rules);

        $this->assertSame(['total' => 300], $result);
    }

    #[Test]
    public function testProcessorWithStaticValue(): void
    {
        $source = ['name' => 'John'];
        $rules = [
            'status' => ['processor' => 'active'],
            'role' => ['processor' => 'admin'],
        ];

        $result = Dataset::map($source, $rules);

        $this->assertSame(['status' => 'active', 'role' => 'admin'], $result);
    }

    #[Test]
    public function testProcessorWithSourceValue(): void
    {
        $source = ['price' => 100];
        $rules = [
            'price_with_tax' => [
                'source' => 'price',
                'processor' => fn($price) => $price * 1.2,
            ],
        ];

        $result = Dataset::map($source, $rules);

        $this->assertSame(['price_with_tax' => 120.0], $result);
    }

    // ==========================================
    // Default значения
    // ==========================================

    #[DataProvider('provideDefaultCases')]
    #[Test]
    public function testDefaultValues(array $source, array $rules, array $expected): void
    {
        $result = Dataset::map($source, $rules);
        $this->assertSame($expected, $result);
    }

    public static function provideDefaultCases(): array
    {
        return [
            'Static default' => [
                ['name' => 'John'],
                ['name' => [], 'role' => ['default' => 'user']],
                ['name' => 'John', 'role' => 'user'],
            ],
            'Callable default' => [
                [],
                ['created_at' => ['default' => fn() => '2026-06-12']],
                ['created_at' => '2026-06-12'],
            ],
            'Default with source access' => [
                ['user_id' => 5],
                ['user_id' => [], 'profile_url' => [
                    'default' => fn($src) => "/users/{$src['user_id']}"
                ]],
                ['user_id' => 5, 'profile_url' => '/users/5'],
            ],
        ];
    }

    #[Test]
    public function testDefaultUndefinedValue(): void
    {
        $source = ['name' => 'John'];
        $rules = ['name' => [], 'age' => []];

        $result = Dataset::map($source, $rules, 'N/A');

        $this->assertSame(['name' => 'John', 'age' => 'N/A'], $result);
    }

    // ==========================================
    // Приведение типов
    // ==========================================

    #[DataProvider('provideTypeCastingCases')]
    #[Test]
    public function testTypeCasting(array $source, array $rules, array $expected): void
    {
        $result = Dataset::map($source, $rules);
        $this->assertSame($expected, $result);
    }

    public static function provideTypeCastingCases(): array
    {
        return [
            'String to int' => [
                ['age' => '25'],
                ['age' => ['type' => 'int']],
                ['age' => 25],
            ],
            'String to float' => [
                ['price' => '19.99'],
                ['price' => ['type' => 'float']],
                ['price' => 19.99],
            ],
            'String to bool (yes)' => [
                ['active' => 'yes'],
                ['active' => ['type' => 'bool']],
                ['active' => true],
            ],
            'String to bool (no)' => [
                ['active' => 'no'],
                ['active' => ['type' => 'bool']],
                ['active' => false],
            ],
            'Int to string' => [
                ['code' => 123],
                ['code' => ['type' => 'string']],
                ['code' => '123'],
            ],
            'Array cast' => [
                ['data' => 'value'],
                ['data' => ['type' => 'array']],
                ['data' => ['value']],
            ],
            'Object cast' => [
                ['data' => ['key' => 'value']],
                ['data' => ['type' => 'object']],
                ['data' => (object) ['key' => 'value']],
            ],
        ];
    }

    // ==========================================
    // КРИТИЧЕСКИЙ ТЕСТ: Falsy значения
    // ==========================================

    #[Test]
    public function testFalsyValuesAreNotSkipped(): void
    {
        // Это тест на критический баг оригинала: !empty() пропускал 0, false, ''
        $source = [
            'count' => 0,
            'active' => false,
            'name' => '',
            'nullable' => null,
            'empty_array' => [],
        ];

        $rules = [
            'count' => ['type' => 'int'],
            'active' => ['type' => 'bool'],
            'name' => [],
            'nullable' => [],
            'empty_array' => [],
        ];

        $result = Dataset::map($source, $rules);

        // Все falsy значения должны быть сохранены, а не заменены на null!
        $this->assertSame(0, $result['count']);
        $this->assertFalse($result['active']);
        $this->assertSame('', $result['name']);
        $this->assertNull($result['nullable']);
        $this->assertSame([], $result['empty_array']);
    }

    #[Test]
    public function testZeroIsNotReplacedByDefault(): void
    {
        $source = ['quantity' => 0];
        $rules = [
            'quantity' => ['default' => 999],
        ];

        $result = Dataset::map($source, $rules);

        // 0 — это валидное значение, default НЕ должен применяться
        $this->assertSame(0, $result['quantity']);
    }

    #[Test]
    public function testFalseIsNotReplacedByDefault(): void
    {
        $source = ['is_active' => false];
        $rules = [
            'is_active' => ['default' => true],
        ];

        $result = Dataset::map($source, $rules);

        // false — это валидное значение, default НЕ должен применяться
        $this->assertFalse($result['is_active']);
    }

    // ==========================================
    // Отсутствующие ключи
    // ==========================================

    #[Test]
    public function testMissingKeyReturnsNull(): void
    {
        $source = ['name' => 'John'];
        $rules = ['name' => [], 'age' => []];

        $result = Dataset::map($source, $rules);

        $this->assertSame(['name' => 'John', 'age' => null], $result);
    }

    #[Test]
    public function testMissingKeyWithDefault(): void
    {
        $source = [];
        $rules = [
            'status' => ['default' => 'pending'],
        ];

        $result = Dataset::map($source, $rules);

        $this->assertSame(['status' => 'pending'], $result);
    }

    // ==========================================
    // Комплексные сценарии
    // ==========================================

    #[Test]
    public function testComplexMapping(): void
    {
        $source = [
            'user_name' => 'John Doe',
            'user_age' => '30',
            'is_admin' => 'yes',
            'balance' => '1234.56',
        ];

        $rules = [
            'name' => [
                'source' => 'user_name',
                'processor' => fn($name) => strtoupper($name),
            ],
            'age' => [
                'source' => 'user_age',
                'type' => 'int',
            ],
            'admin' => [
                'source' => 'is_admin',
                'type' => 'bool',
            ],
            'balance_formatted' => [
                'source' => 'balance',
                'processor' => fn($balance) => '$' . number_format((float) $balance, 2),
            ],
            'status' => [
                'processor' => 'active',
            ],
            'created_at' => [
                'default' => '2026-06-12',
            ],
        ];

        $result = Dataset::map($source, $rules);

        $expected = [
            'name' => 'JOHN DOE',
            'age' => 30,
            'admin' => true,
            'balance_formatted' => '$1,234.56',
            'status' => 'active',
            'created_at' => '2026-06-12',
        ];

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function testOnlyRuleKeysAreIncluded(): void
    {
        // Правило 3: результирующий массив содержит ТОЛЬКО те ключи, которые указаны в правилах
        $source = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
        $rules = ['a' => [], 'c' => []];

        $result = Dataset::map($source, $rules);

        $this->assertSame(['a' => 1, 'c' => 3], $result);
        $this->assertArrayNotHasKey('b', $result);
        $this->assertArrayNotHasKey('d', $result);
    }

    // ==========================================
    // Edge cases
    // ==========================================

    #[Test]
    public function testEmptySource(): void
    {
        $result = Dataset::map([], ['key' => ['default' => 'value']]);
        $this->assertSame(['key' => 'value'], $result);
    }

    #[Test]
    public function testEmptyRules(): void
    {
        $result = Dataset::map(['a' => 1, 'b' => 2], []);
        $this->assertSame([], $result);
    }

    #[Test]
    public function testProcessorReceivesSourceArray(): void
    {
        $source = ['x' => 10, 'y' => 20];
        $rules = [
            'sum' => [
                'processor' => fn($value, $src) => $src['x'] + $src['y'],
            ],
        ];

        $result = Dataset::map($source, $rules);

        $this->assertSame(['sum' => 30], $result);
    }

    #[Test]
    public function testTypeCastingWithProcessor(): void
    {
        $source = ['value' => '123'];
        $rules = [
            'value' => [
                'processor' => fn($v) => $v . '456',
                'type' => 'int',
            ],
        ];

        $result = Dataset::map($source, $rules);

        // Processor возвращает '123456', затем приводится к int
        $this->assertSame(['value' => 123456], $result);
    }
}