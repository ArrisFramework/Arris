```php
// 1. Простое копирование
$source = ['name' => 'John', 'age' => '25'];
$result = DatasetMapper::map($source, [
    'name' => [],
    'age' => ['type' => 'int'],
]);
// => ['name' => 'John', 'age' => 25]

// 2. Переименование ключей
$source = ['user_name' => 'Jane'];
$result = DatasetMapper::map($source, [
    'username' => ['source' => 'user_name'],
]);
// => ['username' => 'Jane']

// 3. Обработка через callback
$source = ['price' => 100, 'quantity' => 3];
$result = DatasetMapper::map($source, [
    'total' => [
        'processor' => fn($value, $src) => $src['price'] * $src['quantity'],
    ],
]);
// => ['total' => 300]

// 4. Фиксированное значение
$result = DatasetMapper::map([], [
    'status' => ['processor' => 'active'],
]);
// => ['status' => 'active']

// 5. Значение по умолчанию
$source = ['name' => 'John'];
$result = DatasetMapper::map($source, [
    'name' => [],
    'role' => ['default' => 'user'],
]);
// => ['name' => 'John', 'role' => 'user']

// 6. Default через callback
$result = DatasetMapper::map([], [
    'created_at' => ['default' => fn() => date('Y-m-d')],
]);
// => ['created_at' => '2026-06-12']

// 7. КРИТИЧЕСКИЙ ТЕСТ: falsy значения
$source = ['count' => 0, 'active' => false, 'name' => ''];
$result = DatasetMapper::map($source, [
    'count' => ['type' => 'int'],
    'active' => ['type' => 'bool'],
    'name' => [],
]);
// => ['count' => 0, 'active' => false, 'name' => '']
// Оригинал вернул бы: ['count' => null, 'active' => null, 'name' => null] — БАГ!

// 8. Приведение типов
$source = ['price' => '19.99', 'count' => '5', 'active' => 'yes'];
$result = DatasetMapper::map($source, [
    'price' => ['type' => 'float'],
    'count' => ['type' => 'int'],
    'active' => ['type' => 'bool'],
]);
// => ['price' => 19.99, 'count' => 5, 'active' => true]
```