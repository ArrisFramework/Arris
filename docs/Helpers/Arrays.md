# Arrays

```markdown
# Arris\Helpers\Arrays

> Статический хелпер для работы с массивами: фильтрация, парсинг строк с типизацией,
> безопасное извлечение значений с whitelist-валидацией, переиндексация PDO-выборок.

**Namespace:** `Arris\Helpers`  
**Реализует:** `ArraysInterface`  
**Зависимости:** `Arris\Helpers\Dataset` (для `explodeToType` при строковых типах)

---

## Оглавление

1. [filter()](#filter)
2. [explodeToType()](#explodetotype)
3. [filterArrayForAllowed()](#filterarrayforallowed)
4. [groupDatasetByColumn()](#groupdatasetbycolumn)
5. [Тестирование](#тестирование)

---

## Методы

### `filter()`

Обёртка над `array_filter` с теми же сигнатурами.

```php
public static function filter(
    array $input,
    ?callable $callback = null,
    int $flag = 0
): array
```

| Параметр   | Тип             | По умолчанию | Описание                                       |
|------------|-----------------|--------------|------------------------------------------------|
| `$input`   | `array`         | —            | Входной массив                                 |
| `$callback`| `callable\|null`| `null`       | Функция фильтрации. Если `null` — удаляются falsy |
| `$flag`    | `int`           | `0`          | `ARRAY_FILTER_USE_KEY`, `ARRAY_FILTER_USE_BOTH`|

**Примеры:**

```php
// Удаление пустых значений
Arrays::filter([0, 1, '', 'hello', null]);
// => [1, 'hello']

// Фильтрация по ключу
Arrays::filter(
    ['a' => 1, 'b' => 2, 'c' => 3],
    fn($key) => $key !== 'b',
    ARRAY_FILTER_USE_KEY
);
// => ['a' => 1, 'c' => 3]
```

---

### `explodeToType()`

Разбивает строку по разделителю и приводит каждый элемент к указанному типу
или применяет callback.

```php
public static function explodeToType(
    string $string,
    string $separator = ' ',
    string|callable|array|null $typeOrCallback = null
): array
```

| Параметр           | Тип                              | По умолчанию | Описание                                              |
|--------------------|----------------------------------|--------------|-------------------------------------------------------|
| `$string`          | `string`                         | —            | Исходная строка                                       |
| `$separator`       | `string`                         | `' '`        | Разделитель                                           |
| `$typeOrCallback`  | `string\|callable\|array\|null`  | `null`       | Тип, callback или массив типов/callback'ов            |

**Варианты `$typeOrCallback`:**

| Значение            | Поведение                                            |
|---------------------|------------------------------------------------------|
| `null`              | Элементы возвращаются как строки                     |
| `'int'`, `'float'`… | Каждый элемент приводится через `Dataset::castToType`|
| `callable`          | Callback применяется к каждому элементу              |
| `array`             | N-й элемент обрабатывается N-м элементом массива     |

**Примеры:**

```php
// Без приведения типов
Arrays::explodeToType('a,b,c', ',');
// => ['a', 'b', 'c']

// Все элементы → int
Arrays::explodeToType('1 2 3', ' ', 'int');
// => [1, 2, 3]

// Все элементы → float
Arrays::explodeToType('1.5,2.7', ',', 'float');
// => [1.5, 2.7]

// Callback
Arrays::explodeToType('a,b,c', ',', fn($v) => strtoupper($v));
// => ['A', 'B', 'C']

// Массив обработчиков (каждый к своему элементу)
Arrays::explodeToType('42,3.14,hello', ',', ['int', 'float', 'string']);
// => [42, 3.14, 'hello']

// Смешанный массив: тип + callback
Arrays::explodeToType('100,hello', ',', ['int', fn($v) => strtoupper($v)]);
// => [100, 'HELLO']
```

> ⚠️ При использовании строковых типов (`'int'`, `'float'`, `'bool'`, `'string'`)
> требуется наличие класса `Arris\Helpers\Dataset` с методом `castToType()`.

---

### `filterArrayForAllowed()`

Безопасно извлекает значение из массива по ключу.
Если ключ отсутствует или значение не входит в whitelist — возвращает default.
Используется **строгое сравнение** (`in_array(..., true)`).

```php
public static function filterArrayForAllowed(
    array $inputArray,
    string|int $requiredKey,
    array $allowedValues,
    mixed $defaultValue
): mixed
```

| Параметр         | Тип            | Описание                        |
|------------------|----------------|---------------------------------|
| `$inputArray`    | `array`        | Входной массив                  |
| `$requiredKey`   | `string\|int`  | Искомый ключ                    |
| `$allowedValues` | `array`        | Whitelist допустимых значений   |
| `$defaultValue`  | `mixed`        | Возвращается при несовпадении   |

**Примеры:**

```php
Arrays::filterArrayForAllowed(
    ['status' => 'active'],
    'status',
    ['active', 'inactive'],
    'unknown'
);
// => 'active'

Arrays::filterArrayForAllowed(
    ['status' => 'banned'],
    'status',
    ['active', 'inactive'],
    'unknown'
);
// => 'unknown'

// Строгое сравнение: строка '1' !== int 1
Arrays::filterArrayForAllowed(
    ['id' => '1'],
    'id',
    [1],
    0
);
// => 0
```

---

### `groupDatasetByColumn()`

Переиндексирует массив ассоциативных массивов (например, результат `PDO::FETCH_ASSOC`)
по значению указанной колонки.

```php
public static function groupDatasetByColumn(
    array $dataset,
    string|int $column_id
): array
```

| Параметр     | Тип            | Описание                           |
|--------------|----------------|------------------------------------|
| `$dataset`   | `array`        | Массив ассоциативных массивов      |
| `$column_id` | `string\|int`  | Имя колонки для нового ключа       |

**Примеры:**

```php
$dataset = [
    0 => ['id' => 5, 'data' => 10],
    1 => ['id' => 6, 'data' => 12],
    2 => ['id' => 7, 'data' => 14],
];

Arrays::groupDatasetByColumn($dataset, 'id');
// => [
//     5 => ['id' => 5, 'data' => 10],
//     6 => ['id' => 6, 'data' => 12],
//     7 => ['id' => 7, 'data' => 14],
// ]
```

> ⚠️ При дубликатах значений в колонке **последняя строка перезаписывает предыдущую**.

---

## Тестирование

Тесты написаны для **PHPUnit 10+** с использованием PHP 8 атрибутов.

```bash
# Запуск всех тестов
./vendor/bin/phpunit tests/Helpers/ArraysTest.php

# С фильтрацией по TestDox
./vendor/bin/phpunit --testdox tests/Helpers/ArraysTest.php
```

### Структура тестов

| Метод                    | Кол-во тестов | DataProvider |
|--------------------------|:---:|:---:|
| `filter()`               | 5   | —   |
| `explodeToType()`        | 8   | —   |
| `filterArrayForAllowed()`| 7   | ✅  |
| `groupDatasetByColumn()` | 5   | ✅  |
| **Итого**                |**25**|     |

> Тесты для `explodeToType()` со строковыми типами (`'int'`, `'float'`) автоматически
> пропускаются (`markTestSkipped`), если класс `Dataset` отсутствует в проекте.
```

---

### Краткая сводка файлов

| Файл | Назначение |
|---|---|
| `ArraysInterface.php` | Контракт — все 4 метода с PHPDoc |
| `ArraysTest.php` | 25 тестов, PHPUnit 10 атрибуты (`#[Test]`, `#[DataProvider]`, `#[TestDox]`, `#[CoversClass]`) |
| `Arrays.md` | Полная документация с примерами, таблицами параметров и инструкцией по запуску тестов |