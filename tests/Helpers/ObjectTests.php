<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Arris\Helpers\HTTPStatus;
use Arris\Helpers\Objects;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Objects::class)]
final class ObjectTests extends TestCase
{
    /**
     * Тестирует propertyExistsRecursive с объектами и массивами.
     */
    #[DataProvider('providePropertyExistsCases')]
    public function testPropertyExistsRecursive(mixed $data, string $path, bool $expected): void
    {
        $this->assertSame($expected, Objects::propertyExistsRecursive($data, $path));
    }

    public static function providePropertyExistsCases(): array
    {
        // Создаем тестовые данные
        $user = new \stdClass();
        $user->name = 'John';
        $user->address = new \stdClass();
        $user->address->city = 'Moscow';
        $user->address->zip = '123456';

        $array = [
            'user' => [
                'profile' => [
                    'settings' => ['theme' => 'dark']
                ]
            ]
        ];

        return [
            'Объект: свойство существует' => [$user, 'name', true],
            'Объект: вложенное свойство существует' => [$user, 'address->city', true],
            'Объект: свойство не существует' => [$user, 'age', false],
            'Объект: вложенное свойство не существует' => [$user, 'address->country', false],
            'Массив: ключ существует' => [$array, 'user', true],
            'Массив: вложенный ключ существует' => [$array, 'user->profile->settings', true],
            'Массив: ключ не существует' => [$array, 'admin', false],
            'Массив: вложенный ключ не существует' => [$array, 'user->profile->avatar', false],
            'Скалярное значение в середине пути' => [$user, 'name->first', false],
        ];
    }

    /**
     * Тестирует propertyGetRecursive с объектами и массивами.
     */
    #[DataProvider('providePropertyGetCases')]
    public function testPropertyGetRecursive(mixed $data, string $path, mixed $default, mixed $expected): void
    {
        $this->assertSame($expected, Objects::propertyGetRecursive($data, $path, '->', $default));
    }

    public static function providePropertyGetCases(): array
    {
        $user = new \stdClass();
        $user->name = 'John';
        $user->address = new \stdClass();
        $user->address->city = 'Moscow';

        $array = [
            'user' => [
                'profile' => [
                    'theme' => 'dark',
                    'count' => 42
                ]
            ]
        ];

        return [
            'Объект: получение значения' => [$user, 'name', null, 'John'],
            'Объект: вложенное значение' => [$user, 'address->city', null, 'Moscow'],
            'Объект: несуществующее свойство с дефолтом' => [$user, 'age', 'Unknown', 'Unknown'],
            'Объект: несуществующее вложенное свойство' => [$user, 'address->country', 'N/A', 'N/A'],
            'Массив: получение значения' => [$array, 'user->profile->theme', null, 'dark'],
            'Массив: числовое значение' => [$array, 'user->profile->count', null, 42],
            'Массив: несуществующий ключ с дефолтом' => [$array, 'user->profile->avatar', 'default.png', 'default.png'],
            'Скалярное значение в середине пути' => [$user, 'name->first', null, null],
        ];
    }

    /**
     * Тестирует работу с кастомными сепараторами.
     */
    public function testCustomSeparator(): void
    {
        $data = new \stdClass();
        $data->user = new \stdClass();
        $data->user->name = 'John';

        $this->assertTrue(Objects::propertyExistsRecursive($data, 'user.name', '.'));
        $this->assertSame('John', Objects::propertyGetRecursive($data, 'user.name', '.'));
    }

    /**
     * Тестирует смешанные структуры (объект + массив).
     */
    public function testMixedStructures(): void
    {
        $mixed = json_decode('{"user": {"posts": [{"id": 1}, {"id": 2}]}}');

        $this->assertTrue(Objects::propertyExistsRecursive($mixed, 'user->posts->0->id'));
        $this->assertSame(1, Objects::propertyGetRecursive($mixed, 'user->posts->0->id'));
        $this->assertSame(2, Objects::propertyGetRecursive($mixed, 'user->posts->1->id'));
    }

    /**
     * Тестирует edge cases.
     */
    #[Test]
    public function testEdgeCases(): void
    {
        $obj = new \stdClass();
        $obj->name = 'John';

        // Пустой путь
        $this->assertFalse(Objects::propertyExistsRecursive($obj, ''));

        // Null значение
        $this->assertFalse(Objects::propertyExistsRecursive(null, 'name'));

        // Скалярное значение вместо объекта
        $this->assertFalse(Objects::propertyExistsRecursive('string', 'length'));

        // Получение null значения (существует, но равно null)
        $obj->nullable = null;
        $this->assertTrue(Objects::propertyExistsRecursive($obj, 'nullable'));
        $this->assertNull(Objects::propertyGetRecursive($obj, 'nullable', '->', 'default'));
    }
}