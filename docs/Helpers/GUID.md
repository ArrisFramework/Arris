# GUID 

```php
public static function generateUuid(bool $uppercase = false): string

public static function isValidUuid(string $uuid): bool
```

```php

// Базовая генерация (lowercase, стандарт)
$uuid = GuidGenerator::generateUuid();
// => '550e8400-e29b-41d4-a716-446655440000'

// Uppercase (для совместимости со старым кодом)
$uuid = GuidGenerator::generateUuid(uppercase: true);
// => '550E8400-E29B-41D4-A716-446655440000'

// Старый метод (deprecated, но работает)
$uuid = GuidGenerator::GUID();
// => '550E8400-E29B-41D4-A716-446655440000'

// UUID v7 (сортируемый по времени)
$uuid = GuidGenerator::generateUuidV7();
// => '018f3a7c-8b5e-7d9a-8c3f-2e1d4c5b6a7f'

// Валидация
GuidGenerator::isValidUuid('550e8400-e29b-41d4-a716-446655440000'); // true
GuidGenerator::isValidUuid('not-a-uuid'); // false


```

Старый метод GUID() сохранён как алиас, но помечен @deprecated. Это позволяет постепенно мигрировать legacy-код.

# Альтернатива

```
composer require ramsey/uuid
```

```php
use Ramsey\Uuid\Uuid;

$uuid = Uuid::uuid4()->toString(); // '550e8400-e29b-41d4-a716-446655440000'
$uuid = Uuid::uuid7()->toString(); // UUID v7
```