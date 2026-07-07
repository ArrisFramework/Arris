# Str

> Fluent-строка с цепочечными вызовами. Позволяет combinable-преобразования без временных переменных.

**Namespace:** `Arris\Util`  
**Реализует:** `Stringable`

---

## Зачем

Статические хелперы (`Helpers\Strings`) удобны для разовых операций, но при цепочке преобразований
приходится вкладывать вызовы или заводить переменные:

```php
$result = Strings::slug(trim(mb_strtolower($input)));
```

`Str` делает то же самое через чейнинг:

```php
$result = Str::of($input)->trim()->lower()->slug()->toString();
```

---

## Создание

```php
$s = new Str('hello');
$s = Str::of('hello');    // статический shortcut
```

---

## Методы

### Регистр

| Метод | Описание |
|---|---|
| `lower()` | mb_strtolower |
| `upper()` | mb_strtoupper |
| `ucfirst()` | Верхний регистр первого символа |
| `lcfirst()` | Нижний регистр первого символа |

### Проверки

| Метод | Описание | Возврат |
|---|---|---|
| `contains(string $needle)` | Содержит подстроку | `bool` |
| `containsAll(array $needles)` | Содержит все подстроки | `bool` |
| `containsAny(array $needles)` | Содержит хотя бы одну | `bool` |
| `startsWith(string $needle)` | Начинается с | `bool` |
| `endsWith(string $needle)` | Заканчивается на | `bool` |
| `isEmpty()` | Пустая строка | `bool` |
| `isNotEmpty()` | Непустая | `bool` |
| `isBlank()` | После trim === '' | `bool` |

### Вырезание

| Метод | Описание |
|---|---|
| `after(string $delimiter)` | Всё после первого вхождения |
| `afterLast(string $delimiter)` | Всё после последнего |
| `before(string $delimiter)` | Всё до первого вхождения |
| `beforeLast(string $delimiter)` | Всё до последнего |
| `substr(int $start, ?int $length)` | mb_substr |
| `limit(int $limit, string $end = '...')` | Обрезает до лимита символов |
| `words(int $words, string $end = '...')` | Обрезает до N слов |

### Модификация

| Метод | Описание |
|---|---|
| `replace(string $search, string $replace)` | str_replace |
| `replaceRegex(string $pattern, string $replacement)` | preg_replace |
| `trim()`, `trimLeft()`, `trimRight()` | Обрезка пробелов |
| `padLeft(int $length, string $pad = ' ')` | str_pad LEFT |
| `padRight(int $length, string $pad = ' ')` | str_pad RIGHT |
| `padBoth(int $length, string $pad = ' ')` | str_pad BOTH |
| `repeat(int $times)` | str_repeat |
| `reverse()` | Разворот строки |
| `shuffle()` | Перемешивание символов |
| `slug(string $separator = '-')` | Транслитерация в URL-friendly |
| `append(string $string)` | Добавление в конец |
| `prepend(string $string)` | Добавление в начало |

### Разбор

| Метод | Описание |
|---|---|
| `match(string $pattern)` | Первое совпадение по regex или null |
| `matchAll(string $pattern)` | Все совпадения |
| `explode(string $separator = ' ')` | Разделение в массив |
| `split(string $pattern)` | preg_split |

### Преобразование

| Метод | Описание |
|---|---|
| `toString()` | Вернуть строку |
| `__toString()` | string-контекст |
| `length()` | mb_strlen |
| `jsonSerialize()` | Для json_encode |

---

## Примеры

```php
// Slug
Str::of(' Hello World! ')->trim()->lower()->slug();       // 'hello-world'

// Извлечение домена из email
Str::of('user@example.com')->after('@');                   // 'example.com'

// Имя файла без расширения
Str::of('photo.jpg')->beforeLast('.');                     // 'photo'

// Проверка
Str::of('foo@bar')->contains('@');                         // true

// Обрезка
Str::of('A long text here')->limit(10);                    // 'A long ...'

// Чейнинг
Str::of('  Some TEXT ')
    ->trim()
    ->lower()
    ->ucfirst()                                            // 'Some text'
    ->replace('text', 'string')
    ->toString();                                          // 'Some string'

// Работа с json
json_encode(Str::of('hello'));                             // '"hello"'
echo Str::of('world');                                     // 'world'
```

---

## Тестирование

```bash
./vendor/bin/phpunit tests/Util/StrTest.php
```
