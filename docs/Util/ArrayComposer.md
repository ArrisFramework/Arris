# ArrayComposer

> Fluent-обёртка над рекурсивным слиянием массивов. Две стратегии: `patch` (replace) и `merge` (append),
> поддержка удаления ключей через `null`, кеширование результата и снепшоты через `save()`.

**Namespace:** `Arris\Util`  
**Реализует:** `ArrayComposerInterface`, `JsonSerializable`

---

## Конструктор

```php
public function __construct(array $original, bool $nullUnsetKeys = false)
```

| Параметр | Тип | По умолчанию | Описание |
|---|---|---|---|
| `$original` | `array` | — | Базовый массив |
| `$nullUnsetKeys` | `bool` | `false` | Если `true` — `null` в значении удаляет ключ |

---

## Методы

### `patch(array ...$arrays): self`

Рекурсивное слияние с **заменой** значений (аналог `array_replace_recursive`).
Скалярные значения перезаписываются, массивы сливаются рекурсивно.

```php
$result = (new ArrayComposer(['a' => 1, 'b' => ['x' => 10]]))
    ->patch(['a' => 2, 'b' => ['y' => 20]])
    ->toArray();
// ['a' => 2, 'b' => ['x' => 10, 'y' => 20]]
```

### `merge(array ...$arrays): self`

Рекурсивное слияние **без замены** (аналог `array_merge_recursive`).
Одинаковые ключи со скалярными значениями группируются в массивы.

```php
$result = (new ArrayComposer(['a' => 1]))
    ->merge(['a' => 2])
    ->toArray();
// ['a' => [1, 2]]
```

### `save(): self`

"Компилирует" текущее состояние и возвращает **новый** инстанс `ArrayComposer`
с зафиксированным результатом. Позволяет строить цепочки с промежуточными снепшотами.

```php
$snapshot = $composer->patch($a)->save(); // новый инстанс
$snapshot->patch($b); // не влияет на оригинал
```

### `toArray(): array`

Возвращает результирующий массив. Результат кешируется до следующей мутации.

### `asArray(): array`

Алиас `toArray()`.

### `jsonSerialize(): array`

Для `json_encode()`.

### `__toString(): string`

Pretty-printed JSON.

---

## Стратегия `nullUnsetKeys`

| Режим | `patch(['key' => null])` |
|---|---|
| `false` (по умолч.) | Ключу присваивается `null` |
| `true` | Ключ удаляется из массива |

```php
(new ArrayComposer(['a' => 1, 'b' => 2], true))
    ->patch(['a' => null])
    ->toArray();
// ['b' => 2]
```

Работает на любом уровне вложенности:

```php
(new ArrayComposer(['db' => ['host' => 'localhost', 'port' => 3306]], true))
    ->patch(['db' => ['port' => null]])
    ->toArray();
// ['db' => ['host' => 'localhost']]
```

---

## Чейнинг

Все методы `patch()`, `merge()` и `save()` возвращают `self`:

```php
$result = (new ArrayComposer($base))
    ->patch($override)
    ->merge($extra)
    ->save()
    ->patch($final)
    ->toArray();
```

---

## Примеры

### Базовое слияние конфигов

```php
$defaults = [
    'app' => ['mode' => 'dev', 'url' => 'http://localhost'],
    'db'  => ['host' => 'localhost'],
];

$override = [
    'app' => ['url' => 'https://example.com'],
    'db'  => ['name' => 'production'],
];

$config = (new ArrayComposer($defaults))
    ->patch($override)
    ->toArray();
// ['app' => ['mode' => 'dev', 'url' => 'https://example.com'],
//  'db'  => ['host' => 'localhost', 'name' => 'production']]
```

### Удаление чувствительных данных

```php
$config = (new ArrayComposer($fullConfig, true))
    ->patch(['db' => ['password' => null]]) // удаляем пароль
    ->toArray();
```

### merge vs patch

```php
$composer = new ArrayComposer(['a' => ['x' => 1]]);
$composer->patch(['a' => ['x' => 2]]);
// ['a' => ['x' => 2]] — замена

$composer = new ArrayComposer(['a' => ['x' => 1]]);
$composer->merge(['a' => ['x' => 2]]);
// ['a' => ['x' => [1, 2]]] — группировка в массив
```

---

## Тестирование

```bash
./vendor/bin/phpunit tests/Util/ArrayComposerTest.php
```

Тесты покрывают: patch, merge, nullUnsetKeys, чейнинг, save, json-сериализацию,
числовые ключи, пустые массивы, граничные случаи с `0` и `false`.
