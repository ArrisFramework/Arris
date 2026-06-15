# Arris Hook System 🪝

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-8892BF.svg)](https://packagist.org/packages/karelwintersky/arris)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Arris Hook** — это легковесная, высокопроизводительная система событий (Event Emitter) для PHP 8.2+.  
Класс объединяет в себе мощный движок подписок (с поддержкой wildcards и приоритетов) и удобный статический фасад для использования в любом месте приложения, включая шаблоны Smarty.

## ✨ Особенности

- ⚡ **Статический API**: Не нужно передавать инстанс эмиттера через DI-контейнер. Вызывайте `Hook::on()` и `Hook::run()` из любого места.
- 🃏 **Wildcard-подписки**: Подписывайтесь на группы событий через `*` (например, `user:*` поймает `user:login`, `user:logout` и т.д.).
- 🔢 **Приоритеты**: Управляйте порядком выполнения слушателей (меньшее число = раньше вызов).
- 🔒 **Безопасность типов**: Полная типизация PHP 8.2, никаких скрытых багов с `empty()` или ссылками.
- 🎨 **Интеграция со Smarty**: Нативная поддержка вызова хуков прямо из шаблонов.
- 🧹 **Чистая архитектура**: Объединяет логику `EventEmitter` и фасада `Hook` в один класс без лишних абстракций.

---

## 📦 Установка

Через Composer (как часть ядра Arris):

```bash
composer require karelwintersky/arris
```

---

## 🚀 Быстрый старт

### Регистрация слушателей

```php
use Arris\Hook;

// Инициализация (опционально, только если нужен сброс состояния)
Hook::init();

// Простая подписка
Hook::on('post:add:comment', function(int $postId, string $text) {
    error_log("New comment #{$postId}: {$text}");
});

// Подписка с приоритетом (90 выполнится раньше, чем 100)
Hook::on('example', fn() => print "Second\n", 100);
Hook::on('example', fn() => print "First\n", 90);

// Wildcard: подпишется на post:add:comment, post:add:topic, post:update:*
Hook::on('post:add:*', function(string $eventData) {
    echo "Caught post action: {$eventData}\n";
});

// Однократная подписка (автоматически отпишется после первого вызова)
Hook::once('app:init', function() {
    echo "App initialized (only once)\n";
});
```

### Запуск событий

```php
// Запуск с аргументами
Hook::run('post:add:comment', [42, 'Hello World']);

// Запуск с прерыванием цепочки
$success = Hook::run('payment:process', [$order], function() use ($shouldStop) {
    return !$shouldStop; // Вернет false → остановит выполнение остальных слушателей
});

// Если ни одного слушателя нет, run() вернет false
if (!Hook::run('optional:event')) {
    echo "No handlers registered\n";
}
```

### Отписка и очистка

```php
// Удалить конкретный слушатель
$handler = fn() => null;
Hook::on('test', $handler);
Hook::off('test', $handler);

// Удалить ВСЕ слушатели конкретного события
Hook::removeAllListeners('post:add:*');

// Полный сброс системы (удалить ВСЁ)
Hook::removeAllListeners();
```

---

## 🎨 Использование в Smarty

### Вариант 1: Прямой доступ к статическому классу

Если политика безопасности Smarty разрешает доступ к классам:

```smarty
{* Запуск хука без возврата значения *}
{Hook::run('template:header')}

{* Запуск хука с сохранением результата *}
{assign var=cartTotal value=Hook::run('cart:calculate', [$cartItems])}

{* Проверка наличия слушателей *}
{if Hook::getListeners('sidebar:widgets')|count > 0}
    <aside class="sidebar">
        {Hook::run('sidebar:widgets')}
    </aside>
{/if}
```

### Вариант 2: Через `registerClass` (рекомендуемый)

```php
$smarty = new Smarty();
$smarty->registerClass('Hook', \Arris\Hook::class);
```

```smarty
{* Теперь Hook доступен как зарегистрированный класс *}
{Hook::run('footer:scripts')}
```

### Вариант 3: Как модификатор для фильтрации данных

```php
// В bootstrap.php
$smarty->registerPlugin('modifier', 'apply_hook', function($value, string $hookName) {
    \Arris\Hook::run($hookName, [&$value]);
    return $value;
});
```

```smarty
{* Применяем хук-фильтр к переменной *}
<h1>{$title|apply_hook:'filter:page_title'}</h1>
<p>{$content|apply_hook:'filter:sanitize_html'|nl2br}</p>
```

---

## 🏗 Архитектура

### Почему один класс вместо двух?

Ранее система состояла из `EventEmitter` (движок) и `Hook` (статический фасад). Это создавало проблемы:
- Лишняя аллокация объекта `new EventEmitter()` при каждом `init()`
- Риск NPE если `init()` не был вызван
- Два файла вместо одного

**Текущая реализация** использует чисто статические свойства. Это:
- ✅ Быстрее (нет накладных расходов на создание объектов)
- ✅ Безопаснее (невозможно использовать неинициализированный эмиттер)
- ✅ Проще (один класс = одна ответственность)

### Производительность

- **Кэширование слушателей**: После первой сортировки по приоритету результат кэшируется в `$listenerIndex`. Повторные `run()` для того же события работают за O(1).
- **Сброс кэша**: Индекс автоматически инвалидируется при любом `on()`, `off()` или `removeAllListeners()`.
- **Прямые вызовы**: Вместо медленного `call_user_func_array()` используется `$callback(...$args)` (на ~15-20% быстрее в горячих путях).

---

## 📋 API Reference

| Метод | Описание |
|-------|----------|
| `Hook::init()` | Сброс всех слушателей (опционально) |
| `Hook::on(event, callback, priority)` | Подписаться на событие |
| `Hook::register(...)` | Алиас `on()` для совместимости |
| `Hook::once(event, callback, priority)` | Подписаться ровно один раз |
| `Hook::off(event, callback)` | Отписать конкретный слушатель |
| `Hook::removeAllListeners(?event)` | Удалить все/конкретные слушатели |
| `Hook::run(event, args, ?continueCb)` | Запустить событие, вернуть `bool` |
| `Hook::getListeners(event)` | Получить отсортированный массив слушателей |

---

## 🧪 Тестирование

```php
protected function setUp(): void
{
    // Изолируем тесты друг от друга
    \Arris\Hook::removeAllListeners();
}

public function testWildcardListeners(): void
{
    $caught = [];
    \Arris\Hook::on('user:*', function($data) use (&$caught) {
        $caught[] = $data;
    });
    
    \Arris\Hook::run('user:login', ['alice']);
    \Arris\Hook::run('user:logout', ['bob']);
    \Arris\Hook::run('post:create', ['ignored']); // Не должно попасть
    
    $this->assertEquals(['alice', 'bob'], $caught);
}
```

---

## 📝 Лицензия

MIT © [Karel Wintersky](https://github.com/KarelWintersky)

