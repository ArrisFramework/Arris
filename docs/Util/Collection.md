# Collection

> Fluent-обёртка над индексированным массивом с map/filter/reduce/sort/groupBy и полным
> набором методов для трансформации данных. Базовая идея — ramsey/collection, но без внешних зависимостей.

**Namespace:** `Arris\Util`  
**Реализует:** `ArrayAccess`, `Countable`, `IteratorAggregate`, `JsonSerializable`

---

## Зачем

В фреймворке уже есть `ArrayComposer` (слияние массивов) и `Arrays` (статические хелперы),
но не было класса для цепочечной обработки списков:

```php
// Было — временные переменные и вложенные вызовы
$filtered = array_filter($items, fn($v) => $v['active']);
$names = array_map(fn($v) => $v['name'], $filtered);
sort($names);

// Стало — fluent chain
$names = Collection::of($items)
    ->filter(fn($v) => $v['active'])
    ->pluck('name')
    ->sort(fn($a, $b) => $a <=> $b)
    ->toArray();
```

---

## Создание

```php
$c = new Collection([1, 2, 3]);
$c = Collection::of([1, 2, 3]);   // shortcut
$c = new Collection();             // пустая
```

---

## Методы

### Трансформация (возвращают новую Collection)

| Метод | Описание |
|---|---|
| `map(callable $callback)` | `array_map` |
| `filter(?callable $callback)` | `array_filter` |
| `sort(callable $callback)` | `usort` |
| `sortDesc()` | `rsort` |
| `reverse()` | `array_reverse` |
| `slice(int $offset, ?int $length)` | `array_slice` |
| `unique()` | `array_unique` |
| `shuffle()` | Перемешивание |
| `values()` | Переиндексация с 0 |
| `merge(Collection\|array $items)` | `array_merge` |
| `diff(Collection\|array $items)` | `array_diff` |
| `intersect(Collection\|array $items)` | `array_intersect` |
| `keyBy(callable\|string $key)` | Переиндексация по ключу |

### Агрегация (возвращают значение)

| Метод | Описание |
|---|---|
| `first()` | Первый элемент или null |
| `last()` | Последний или null |
| `get(int $index)` | По индексу |
| `random()` | Случайный элемент |
| `reduce(callable, $initial)` | `array_reduce` |
| `count()` | Количество элементов |
| `isEmpty()` / `isNotEmpty()` | Проверка пустоты |
| `contains(mixed $value)` | Строгое вхождение |
| `indexOf(mixed $value)` | Поиск индекса |
| `keys()` | Все ключи |
| `implode(string $glue = ',')` | `implode` |
| `every(callable)` | Все элементы проходят проверку |
| `some(callable)` | Хотя бы один проходит |

### Поиск и группировка

| Метод | Описание |
|---|---|
| `pluck(string $key)` | Извлечь колонку в массив |
| `groupBy(callable\|string $key)` | Группировка по ключу → `array[]` |
| `search(callable $callback)` | Первый подходящий элемент |
| `tap(callable $callback)` | Самоанализ (возвращает $this) |
| `pipe(callable $callback)` | Передать в функцию |

### Модификация исходной коллекции

| Метод | Описание |
|---|---|
| `add(mixed $item)` | Добавить в конец |
| `push(...$items)` | array_push |
| `pop()` | array_pop |
| `shift()` | array_shift |
| `unshift(...$items)` | array_unshift |

### Chunking

| Метод | Описание |
|---|---|
| `chunk(int $size)` | Разбить на `Collection[]` |

### Интерфейсы

| Метод | Описание |
|---|---|
| `all()` / `toArray()` | Вернуть массив |
| `getIterator()` | foreach-совместимость |
| `jsonSerialize()` | `json_encode` compatibility |
| `ArrayAccess` | `$c[0]`, `$c[0] = 'x'`, `isset($c[0])`, `unset($c[0])` |

---

## Примеры

```php
// Цепочка
$result = Collection::of($users)
    ->filter(fn($u) => $u['active'])
    ->sort(fn($a, $b) => $a['name'] <=> $b['name'])
    ->pluck('name');

// Группировка
$byRole = Collection::of($users)->groupBy('role');
// ['admin' => [...], 'user' => [...]]

// Проверки
Collection::of([1, 2, 3])->every(fn($v) => $v > 0);  // true
Collection::of([1, 2, 3])->some(fn($v) => $v > 2);   // true

// Срез + слияние
$c = Collection::of([1, 2, 3, 4, 5])
    ->slice(0, 3)
    ->merge([6, 7]);
// [1, 2, 3, 6, 7]

// Chunk
$pages = Collection::of(range(1, 10))->chunk(3);
// [Collection(1,2,3), Collection(4,5,6), Collection(7,8,9), Collection(10)]

// foreach
foreach (Collection::of(['a', 'b']) as $item) { ... }

// ArrayAccess
$c = Collection::of([10, 20]);
$c[0]; // 10
$c[] = 30;

// json
json_encode(Collection::of([1, 2])); // '[1,2]'

// tap/pipe для отладки
Collection::of($data)
    ->tap(fn($c) => Logger::debug('before filter', $c->toArray()))
    ->filter(...)
    ->pipe(fn($c) => $this->process($c->toArray()));
```

---

## Тестирование

```bash
./vendor/bin/phpunit tests/Util/CollectionTest.php
```
