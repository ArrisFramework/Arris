<?php

namespace Tests\Util;

use Arris\Util\ArrayComposer;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ArrayComposerTest extends TestCase
{
    private array $baseConfig;

    protected function setUp(): void
    {
        $this->baseConfig = [
            'app' => [
                'mode' => 'dev',
                'root' => '/var/www',
                'domain' => 'http://localhost',
            ],
            'db' => [
                'host' => 'localhost',
                'port' => 3306,
                'name' => 'test_db',
            ],
        ];
    }

    #[Test]
    public function constructorStoresOriginalArray(): void
    {
        $merger = new ArrayComposer(['key' => 'value']);
        $this->assertEquals(['key' => 'value'], $merger->toArray());
    }

    #[Test]
    public function patchReplacesScalarValues(): void
    {
        $merger = new ArrayComposer($this->baseConfig);
        $merger->patch(['app' => ['domain' => 'https://example.com']]);

        $result = $merger->toArray();
        $this->assertEquals('https://example.com', $result['app']['domain']);
        $this->assertEquals('dev', $result['app']['mode']); // осталось без изменений
    }

    #[Test]
    public function patchAddsNewKeys(): void
    {
        $merger = new ArrayComposer($this->baseConfig);
        $merger->patch(['app' => ['debug' => true]]);

        $result = $merger->toArray();
        $this->assertTrue($result['app']['debug']);
        $this->assertEquals('dev', $result['app']['mode']);
    }

    #[Test]
    public function patchPreservesExistingKeys(): void
    {
        $merger = new ArrayComposer($this->baseConfig);
        $merger->patch(['app' => ['domain' => 'https://example.com']]);

        $result = $merger->toArray();
        $this->assertEquals('dev', $result['app']['mode']);
        $this->assertEquals('/var/www', $result['app']['root']);
    }

    #[Test]
    public function patchWithDeepNesting(): void
    {
        $original = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'a' => 1,
                        'b' => 2,
                    ],
                ],
            ],
        ];

        $patch = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'b' => 20,
                        'c' => 30,
                    ],
                ],
            ],
        ];

        $merger = new ArrayComposer($original);
        $merger->patch($patch);

        $result = $merger->toArray();
        $this->assertEquals(1, $result['level1']['level2']['level3']['a']);
        $this->assertEquals(20, $result['level1']['level2']['level3']['b']);
        $this->assertEquals(30, $result['level1']['level2']['level3']['c']);
    }

    #[Test]
    public function patchWithMultipleArraysInOneCall(): void
    {
        $merger = new ArrayComposer(['a' => 1, 'b' => 2]);
        $merger->patch(['b' => 20], ['c' => 30]);

        $result = $merger->toArray();
        $this->assertEquals(['a' => 1, 'b' => 20, 'c' => 30], $result);
    }

    #[Test]
    public function nullUnsetKeysEnabled(): void
    {
        $merger = new ArrayComposer($this->baseConfig, true);
        $merger->patch(['app' => ['domain' => null]]);

        $result = $merger->toArray();
        $this->assertArrayNotHasKey('domain', $result['app']);
        $this->assertEquals('dev', $result['app']['mode']);
    }

    #[Test]
    public function nullUnsetKeysDisabled(): void
    {
        $merger = new ArrayComposer($this->baseConfig, false);
        $merger->patch(['app' => ['domain' => null]]);

        $result = $merger->toArray();
        $this->assertArrayHasKey('domain', $result['app']);
        $this->assertNull($result['app']['domain']);
    }

    #[Test]
    public function nullUnsetKeysRemovesEntireSection(): void
    {
        $merger = new ArrayComposer($this->baseConfig, true);
        $merger->patch(['app' => null]);

        $result = $merger->toArray();
        $this->assertArrayNotHasKey('app', $result);
        $this->assertArrayHasKey('db', $result);
    }

    #[Test]
    public function nullUnsetKeysWithDeepNesting(): void
    {
        $original = ['config' => ['db' => ['host' => 'localhost', 'port' => 3306]]];
        $merger = new ArrayComposer($original, true);
        $merger->patch(['config' => ['db' => ['port' => null]]]);

        $result = $merger->toArray();
        $this->assertArrayHasKey('host', $result['config']['db']);
        $this->assertArrayNotHasKey('port', $result['config']['db']);
    }

    #[Test]
    public function mergeWithoutReplace(): void
    {
        $first = ['app' => ['name' => 'MyApp']];
        $second = ['app' => ['name' => 'OtherApp']];

        $merger = new ArrayComposer($first);
        $merger->merge($second);

        $result = $merger->toArray();
        $this->assertIsArray($result['app']['name']);
        $this->assertEquals(['MyApp', 'OtherApp'], $result['app']['name']);
    }

    #[Test]
    public function mergeMultipleArrays(): void
    {
        $merger = new ArrayComposer(['a' => 1]);
        $merger->merge(['a' => 2], ['a' => 3]);

        $result = $merger->toArray();
        $this->assertEquals([1, 2, 3], $result['a']);
    }

    #[Test]
    public function mergeAddsNewKeys(): void
    {
        $merger = new ArrayComposer(['a' => 1]);
        $merger->merge(['b' => 2]);

        $result = $merger->toArray();
        $this->assertEquals(['a' => 1, 'b' => 2], $result);
    }

    #[Test]
    public function chainingPatchAndMerge(): void
    {
        $original = ['first' => ['a' => 1, 'b' => 2]];

        $merger = new ArrayComposer($original);
        $result = $merger
            ->patch(['first' => ['a' => 10]])
            ->patch(['second' => ['c' => 3]])
            ->merge(['first' => ['a' => 20]])
            ->toArray();

        $this->assertIsArray($result['first']['a']);
        $this->assertEquals([10, 20], $result['first']['a']);
        $this->assertEquals(2, $result['first']['b']);
        $this->assertEquals(3, $result['second']['c']);
    }

    #[Test]
    public function chainingMultiplePatches(): void
    {
        $merger = new ArrayComposer($this->baseConfig);

        $result = $merger
            ->patch(['app' => ['domain' => 'https://example.com']])
            ->patch(['app' => ['debug' => true]])
            ->patch(['db' => ['port' => 5432]])
            ->toArray();

        $this->assertEquals('https://example.com', $result['app']['domain']);
        $this->assertTrue($result['app']['debug']);
        $this->assertEquals(5432, $result['db']['port']);
    }

    #[Test]
    public function saveReturnsNewInstance(): void
    {
        $merger = new ArrayComposer(['key' => 'value']);
        $merger->patch(['key' => 'updated']);

        $newInstance = $merger->save();

        $this->assertInstanceOf(ArrayComposer::class, $newInstance);
        $this->assertNotSame($merger, $newInstance);
        $this->assertEquals(['key' => 'updated'], $newInstance->toArray());
    }

    #[Test]
    public function saveResetsState(): void
    {
        $merger = new ArrayComposer(['key' => 'value']);
        $merger->patch(['key' => 'updated']);

        $newInstance = $merger->save();

        // Проверяем, что новый инстанс можно дальше патчить
        $newInstance->patch(['new_key' => 'new_value']);
        $result = $newInstance->toArray();

        $this->assertEquals('updated', $result['key']);
        $this->assertEquals('new_value', $result['new_key']);
    }

    #[Test]
    public function toArrayReturnsArray(): void
    {
        $merger = new ArrayComposer($this->baseConfig);
        $result = $merger->toArray();

        $this->assertIsArray($result);
        $this->assertEquals($this->baseConfig, $result);
    }

    #[Test]
    public function asArrayIsAliasForToArray(): void
    {
        $merger = new ArrayComposer(['key' => 'value']);
        $this->assertEquals($merger->toArray(), $merger->asArray());
    }

    #[Test]
    public function jsonSerializeWorks(): void
    {
        $merger = new ArrayComposer(['key' => 'value']);
        $json = json_encode($merger);

        $this->assertJson($json);
        $this->assertEquals('{"key":"value"}', $json);
    }

    #[Test]
    public function toStringReturnsPrettyJson(): void
    {
        $merger = new ArrayComposer(['key' => 'value']);
        $string = (string) $merger;

        $this->assertStringContainsString('"key"', $string);
        $this->assertStringContainsString('"value"', $string);
        $this->assertStringContainsString("\n", $string); // pretty print
    }

    #[Test]
    public function immutabilityOfOriginalArray(): void
    {
        $original = ['key' => 'value'];
        $merger = new ArrayComposer($original);
        $merger->patch(['key' => 'updated']);

        // Оригинальный массив не должен измениться
        $this->assertEquals('value', $original['key']);
        $this->assertEquals('updated', $merger->toArray()['key']);
    }

    #[Test]
    public function emptyArrays(): void
    {
        $merger = new ArrayComposer([]);
        $merger->patch(['key' => 'value']);

        $this->assertEquals(['key' => 'value'], $merger->toArray());
    }

    #[Test]
    public function patchWithEmptyArrayDoesNothing(): void
    {
        $merger = new ArrayComposer($this->baseConfig);
        $merger->patch([]);

        $this->assertEquals($this->baseConfig, $merger->toArray());
    }

    #[Test]
    public function mergeWithEmptyArrayDoesNothing(): void
    {
        $merger = new ArrayComposer($this->baseConfig);
        $merger->merge([]);

        $this->assertEquals($this->baseConfig, $merger->toArray());
    }

    #[Test]
    public function numericKeysInPatch(): void
    {
        $original = ['items' => ['a', 'b', 'c']];
        $merger = new ArrayComposer($original);
        $merger->patch(['items' => [1 => 'x']]);

        $result = $merger->toArray();
        $this->assertEquals(['a', 'x', 'c'], $result['items']);
    }

    #[Test]
    public function numericKeysInMerge(): void
    {
        $original = ['items' => ['a', 'b']];
        $merger = new ArrayComposer($original);
        $merger->merge(['items' => ['c', 'd']]);

        $result = $merger->toArray();
        $this->assertEquals(['a', 'b', 'c', 'd'], $result['items']);
    }

    #[Test]
    public function nullUnsetKeysWithStringZero(): void
    {
        $merger = new ArrayComposer(['key' => '0'], true);
        $merger->patch(['key' => null]);

        $result = $merger->toArray();
        $this->assertArrayNotHasKey('key', $result);
    }

    #[Test]
    public function nullUnsetKeysWithIntegerZero(): void
    {
        $merger = new ArrayComposer(['key' => 0], true);
        $merger->patch(['key' => null]);

        $result = $merger->toArray();
        $this->assertArrayNotHasKey('key', $result);
    }

    #[Test]
    public function nullUnsetKeysWithFalse(): void
    {
        $merger = new ArrayComposer(['key' => false], true);
        $merger->patch(['key' => false]); // false !== null

        $result = $merger->toArray();
        $this->assertArrayHasKey('key', $result);
        $this->assertFalse($result['key']);
    }

    #[Test]
    public function complexScenario(): void
    {
        $original = [
            'app' => [
                'mode' => 'dev',
                'url' => 'http://localhost',
                'cache' => ['enabled' => true, 'ttl' => 3600],
            ],
            'db' => [
                'host' => 'localhost',
                'credentials' => ['user' => 'root', 'pass' => 'secret'],
            ],
        ];

        $patch1 = [
            'app' => ['url' => 'https://staging.com', 'cache' => ['ttl' => 7200]],
        ];

        $patch2 = [
            'db' => ['credentials' => ['pass' => null]], // удаляем пароль
            'logging' => ['level' => 'debug'],
        ];

        $merger = new ArrayComposer($original, true);
        $result = $merger
            ->patch($patch1)
            ->patch($patch2)
            ->toArray();

        $this->assertEquals('dev', $result['app']['mode']);
        $this->assertEquals('https://staging.com', $result['app']['url']);
        $this->assertEquals(7200, $result['app']['cache']['ttl']);
        $this->assertTrue($result['app']['cache']['enabled']);
        $this->assertEquals('localhost', $result['db']['host']);
        $this->assertEquals('root', $result['db']['credentials']['user']);
        $this->assertArrayNotHasKey('pass', $result['db']['credentials']);
        $this->assertEquals('debug', $result['logging']['level']);
    }
}