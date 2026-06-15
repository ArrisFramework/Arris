<?php
declare(strict_types=1);

namespace Tests\Helpers;

use Arris\Helpers\Dataset; // Предполагаем, что класс лежит здесь
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Dataset::class)]
class DatasetTest extends TestCase
{
    #[Test]
    public function mapBasicKeysWithoutRules(): void
    {
        $source = ['name' => 'John', 'age' => 30];
        $rules = [
            'name' => [],
            'age' => []
        ];

        $result = Dataset::map($source, $rules);

        $this->assertEquals(['name' => 'John', 'age' => 30], $result);
    }

    #[Test]
    public function mapWithRenamingTargetKey(): void
    {
        $source = ['user_name' => 'Alice'];
        $rules = [
            'username' => [
                'source' => 'user_name',
                'target' => 'username'
            ]
        ];

        $result = Dataset::map($source, $rules);

        $this->assertArrayHasKey('username', $result);
        $this->assertEquals('Alice', $result['username']);
        $this->assertArrayNotHasKey('user_name', $result);
    }

    #[Test]
    public function mapIgnoresSourceKeysNotInRules(): void
    {
        $source = ['id' => 1, 'secret' => 'hidden', 'name' => 'Bob'];
        $rules = [
            'id' => [],
            'name' => []
        ];

        $result = Dataset::map($source, $rules);

        $this->assertEquals(['id' => 1, 'name' => 'Bob'], $result);
        $this->assertArrayNotHasKey('secret', $result);
    }

    #[Test]
    public function mapWithCallableProcessor(): void
    {
        $source = ['price' => 100];
        $rules = [
            'final_price' => [
                'source' => 'price',
                'processor' => fn($v) => $v * 1.2
            ]
        ];

        $result = Dataset::map($source, $rules);

        $this->assertEquals(120.0, $result['final_price']);
    }

    #[Test]
    public function mapWithFixedValueProcessor(): void
    {
        $source = ['status' => 'pending'];
        $rules = [
            'type' => [
                'source' => 'status', // Игнорируется, так как processor фиксирован
                'processor' => 'user_account'
            ]
        ];

        $result = Dataset::map($source, $rules);

        $this->assertEquals('user_account', $result['type']);
    }

    #[Test]
    public function mapProcessorReceivesFullSourceContext(): void
    {
        $source = ['first' => 'John', 'last' => 'Doe'];
        $rules = [
            'full_name' => [
                'source' => 'first',
                'processor' => fn($val, $src) => $val . ' ' . ($src['last'] ?? '')
            ]
        ];

        $result = Dataset::map($source, $rules);

        $this->assertEquals('John Doe', $result['full_name']);
    }

    #[Test]
    public function mapWithTypeCastingInt(): void
    {
        $source = ['count' => '42'];
        $rules = [
            'count' => ['type' => 'int']
        ];

        $result = Dataset::map($source, $rules);

        $this->assertSame(42, $result['count']);
        $this->assertIsInt($result['count']);
    }

    #[Test]
    public function mapWithTypeCastingBool(): void
    {
        $source = ['active' => '1', 'deleted' => '0'];
        $rules = [
            'active' => ['type' => 'bool'],
            'deleted' => ['type' => 'bool']
        ];

        $result = Dataset::map($source, $rules);

        $this->assertTrue($result['active']);
        $this->assertFalse($result['deleted']);
    }

    #[Test]
    public function mapWithDefaultValueWhenKeyMissing(): void
    {
        $source = ['name' => 'Test'];
        $rules = [
            'role' => [
                'default' => 'guest'
            ]
        ];

        $result = Dataset::map($source, $rules);

        $this->assertEquals('guest', $result['role']);
    }

    #[Test]
    public function mapWithDefaultUndefinedValue(): void
    {
        $source = [];
        $rules = [
            'missing_key' => []
        ];

        $result = Dataset::map($source, $rules, 'N/A');

        $this->assertEquals('N/A', $result['missing_key']);
    }

    #[Test]
    public function mapRuleDefaultOverridesGlobalDefault(): void
    {
        $source = [];
        $rules = [
            'key1' => ['default' => 'rule_default'],
            'key2' => []
        ];

        $result = Dataset::map($source, $rules, 'global_default');

        $this->assertEquals('rule_default', $result['key1']);
        $this->assertEquals('global_default', $result['key2']);
    }

    #[Test]
    public function mapHandlesFalsyValuesCorrectly(): void
    {
        // Критический тест: проверяем, что 0, '', false, null не считаются "отсутствующими"
        $source = [
            'zero' => 0,
            'empty_string' => '',
            'false_val' => false,
            'null_val' => null
        ];

        $rules = [
            'zero' => [],
            'empty_string' => [],
            'false_val' => [],
            'null_val' => []
        ];

        $result = Dataset::map($source, $rules);

        $this->assertSame(0, $result['zero']);
        $this->assertSame('', $result['empty_string']);
        $this->assertFalse($result['false_val']);
        $this->assertNull($result['null_val']);
    }

    #[Test]
    public function mapWithComplexNestedProcessor(): void
    {
        $source = ['tags' => 'php,laravel,testing'];
        $rules = [
            'tags_array' => [
                'source' => 'tags',
                'processor' => fn($v) => array_map('trim', explode(',', $v)),
                'type' => 'array' // Хотя processor уже вернет массив, type может быть полезен для валидации
            ]
        ];

        $result = Dataset::map($source, $rules);

        $this->assertEquals(['php', 'laravel', 'testing'], $result['tags_array']);
    }

    #[Test]
    public function mapEmptyRulesReturnsEmptyArray(): void
    {
        $source = ['a' => 1, 'b' => 2];
        $rules = [];

        $result = Dataset::map($source, $rules);

        $this->assertEquals([], $result);
    }
}