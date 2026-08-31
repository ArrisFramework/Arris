# Arris\ErrorHandler 🚨

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-8892BF.svg)](https://packagist.org/packages/karelwintersky/arris)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**ErrorHandler** — ловец необработанных исключений и PHP-ошибок для приложений на Arris.
Единая точка перехвата `set_exception_handler()` / `set_error_handler()` с двумя режимами
(debug / safe), переопределяемой безопасной заглушкой и корректным поведением в CLI-скриптах.

Регистрируется **вручную и в самом начале** приложения — до любых прочих инициализаций.
Если обработчик не зарегистрирован — поведение PHP не меняется.

---

## 🧠 Зачем это нужно

По умолчанию необработанное исключение в PHP даёт «голую» страницу без контекста, а PHP-ошибки
(notice/warning) вообще не бросаются. `ErrorHandler`:

- превращает PHP-ошибки в `\ErrorException` (исключения) через `set_error_handler()`;
- ловит **необработанные** исключения через `set_exception_handler()`;
- в **debug** выводит полный HTML-дамп: класс, message, цепочку `Previous`, стек вызовов
  со сниппетами исходника и аргументами;
- в **safe** (прод) — безопасную заглушку, из которой можно убрать технические детали;
- в **CLI** — плоский текст в STDERR + ненулевой exit-код.

---

## 🚀 Быстрый старт

```php
<?php
declare(strict_types=1);

namespace App;

use Arris\ErrorHandler;

class App extends \Arris\App
{
    public static function init(array $config_files = []): void
    {
        // Регистрируем обработчик до любых инициализаций
        $errorHandler = new ErrorHandler();
        $errorHandler->setDebug(getenv('ENV_STATE') !== 'prod');
        $errorHandler->register();

        self::$config = App::factory($config_files)->getConfig();
    }
}
```

Всё. Дальше любые необработанные исключения и PHP-ошибки попадут в `ErrorHandler`.

---

## 🎛 Режимы: debug vs safe

`setDebug(bool)` выбирает поведение **в вебе** (`handle()`):

| Режим            | Что выводит в вебе при ошибке                    |
|------------------|--------------------------------------------------|
| `debug = true`   | полный HTML-дамп (пути, стек, сниппеты, аргументы) |
| `debug = false`  | безопасная заглушка (по умолчанию: заголовок + message исключения) |

В **CLI** режим не влияет на формат: всегда плоский текст + exit-код.

```php
$eh->setDebug(true);   // dev
$eh->setDebug(false);  // prod
$eh->isDebug();        // текущий режим
```

> ⚠️ Никогда не включайте `debug` в проде: полный HTML-дамп раскрывает абсолютные пути,
> исходный код и значения аргументов. Режим должен задаваться **до** `register()`.

---

## 🛡 Безопасная заглушка (safe-режим)

По умолчанию заглушка выглядит так (`renderSafe()`):

```html
<h1>Что-то пошло не так</h1><p><message головного исключения></p>
```

В отличие от безликой строки «шеф, всё пропало», здесь показывается **message перехваченного
(головного) исключения** — `$e->getMessage()` — а не стек или пути.

### Переопределение заглушки: `safeText(callable)`

Колбэк получает массив `$event` и возвращает переопределения (поля можно задать **частично**):

```php
$errorHandler->safeText(function (array $event): array {
    $event['heading'] = '47 Новостей Ленинградской области';
    $event['text']    = 'У нас что-то сломалось. Мы уже чиним.';
    return $event;
});
```

Событие `$event`:
- `'heading'` — заголовок заглушки (по умолчанию «Что-то пошло не так»);
- `'text'` — текст заглушки (по умолчанию — message исключения).

> ⚠️ **Важно про PHP-массивы.** Менять `$event` внутри колбэка без `return` бесполезно:
> массивы передаются по значению, и изменения пропадут. Колбэк **обязан вернуть** массив
> переопределений (полностью или частично) — иначе заглушка останется дефолтной.

---

## 💻 Поведение в CLI

Для `PHP_SAPI === 'cli'` (или `phpdbg`) `ErrorHandler`:

- пишет плоский дамп в **STDERR** (`toText()`): класс, message, `file:line` + трейс;
- завершает скрипт с **ненулевым exit-кодом** (`exitCode()`):
  - для `\ErrorException` — `getSeverity()` (напр. `E_WARNING` → `2`), т.к. код таких
    исключений всегда `0`;
  - для остальных — `getCode()`;
  - если получилось `0` — fallback на `1`.

Это позволяет CI/крону/мониторингу корректно определять, что скрипт упал.

```bash
$ php -r '$eh = new \Arris\ErrorHandler(); $eh->register(); echo $undefined_var;'
[FATAL] ErrorException: Undefined variable $undefined_var at php shell code:1
#0 {main}
#1 {main}
$ echo $?   # 2 (E_WARNING)
```

---

## 📖 API

### `register(): void`
Регистрирует `set_exception_handler()` и `set_error_handler()`.
Идемпотентна: повторный вызов безопасен (перезаписывает теми же обработчиками).

### `setDebug(bool $debug = true): static`
Включает/выключает debug-режим. Fluent.

### `isDebug(): bool`
Текущий debug-режим.

### `safeText(callable $callback): static`
Задаёт колбэк кастомизации безопасной заглушки. Fluent.

### `handle(\Throwable $e): void`
Точка входа обработчика. Вызывается автоматически из `set_exception_handler()`.
Обычно вызывать напрямую не нужно.

### `renderSafe(\Throwable $e): string`
Возвращает HTML-заглушку для прода (заголовок + text, с учётом `safeText()`).

### `dump(\Throwable $e): string`
Возвращает полный HTML-дамп (debug). Поля: класс, message, `File:line`, `Code`, `Time`,
`Memory`, цепочка `Previous #N`, стек вызовов со сниппетами и аргументами.
Экранирование через `htmlspecialchars`.

### `toText(\Throwable $e): string`
Возвращает плоский текстовый дамп для CLI/логов: `class: message at file:line` + трейс.

---

## 🌓 Тема страницы дампа

Полный HTML-дамп (`dump()`) поддерживает **тёмную и светлую** тему:

- **по умолчанию** — тёмная, но если у пользователя в системе включена светлая тема,
  и он ещё не выбирал явно — страница сама откроется в светлой (`prefers-color-scheme`);
- **вручную** — кнопка-переключатель ☀/☾ в правом верхнем углу шапки дампа;
- выбор запоминается в `localStorage` (`arris-theme`) и применяется при следующей ошибке
  без «вспышки» (тема ставится инлайн-скриптом в `<head>` до отрисовки).

Тема реализована на CSS-переменных: `--bg`, `--fg`, `--muted`, `--heading`, `--class`,
`--accent`, `--prev-border`, `--prev-class`, `--frame-border`, `--frame-head`, `--snippet-bg`,
`--snippet-fg`, `--lineno`, `--hit-bg`, `--hit-fg`. Для кастомизации достаточно переопределить
переменные под `html[data-theme="light"]` / `html[data-theme="dark"]`.

---

## 🧪 Демонстрация

В корне пакета лежит готовый скрипт-демонстрация всех сценариев:

```bash
php error_handler_demo.php
```

Он показывает: safe-default, переопределение заглушки, частичное переопределение,
debug-дамп и CLI-ветку (в сабпроцессе, чтобы не завершать демо сам скрипт).

---

## 📝 Лицензия

MIT © [Karel Wintersky](https://github.com/KarelWintersky)
