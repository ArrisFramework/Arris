# Arris Core 🧱

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-8892BF.svg)](https://packagist.org/packages/karelwintersky/arris)
[![PHPUnit](https://img.shields.io/badge/phpunit-10-green.svg)](https://phpunit.de/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Arris.µFramework** — это легковесное ядро (Application Container) для PHP-приложений. 
Оно предоставляет Service Locator, менеджер конфигураций и реализует паттерн Singleton 
с поддержкой **наследования** (Late Static Binding).

## ✨ Особенности

- 🧬 **Наследуемые синглтоны**: `App::getInstance()` и `MyCustomApp::getInstance()` создают независимые контейнеры, не перезаписывая друг друга (решает [эту проблему](https://stackoverflow.com/questions/3126130/extending-singletons-in-php)).
- ⚙️ **Гибкая конфигурация**: Рекурсивное слияние "жестких" дефолтов класса с пользовательскими конфиг-файлами (`.php`, `.json`, `.yml`).
- 🛡 **Безопасность (PHP 8.2+)**: Использование `readonly` свойств для защиты ядра от случайных мутаций.
- 🚫 **Защита от дурака**: Невозможно случайно клонировать или сериализовать контейнер.
- 🧪 **100% Тестируемость**: Встроенный метод `reset()` для идеальной изоляции Unit-тестов.

---

## 📦 Установка

Через Composer:

```bash
composer require karelwintersky/arris
```

---

## 🚀 Быстрый старт

### 1. Создайте класс приложения

В вашем проекте создайте класс, который наследуется от `Arris\App` и переопределяет (желательно) метод `getDefaultConfig()`.

```php
<?php
declare(strict_types=1);

namespace App;

class App extends \Arris\App
{
    protected function getDefaultConfig(): array
    {
        return [
            'app' => [
                'name' => 'My Awesome App',
                'debug' => false,
            ],
            'database' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3306,
            ],
        ];
    }
}
```

### 2. Инициализация и использование

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\App;

// Создаем инстанс и подгружаем пользовательские конфиги
// (Префикс "?" делает файл опциональным)
$app = App::factory([
    __DIR__ . '/config/config.required.php',
    '?' . __DIR__ . '/config/config.optional.php', 
]);

// Чтение конфига (файлы имеют приоритет над getDefaultConfig)
$dbHost = $app->getConfig('database.host');

// Статический shortcut (удобно в контроллерах)
$appName = App::config('app.name');
```

---

## 🔌 Работа с сервисами (DI)

Arris предоставляет базовый сервис-контейнер, но для **IDE-friendly** автодополнения рекомендуется использовать типизированные геттеры в вашем классе `App`:

```php
class App extends BaseApp
{
    private ?\PDO $pdoInstance = null;

    /**
     * IDE видит тип \PDO и предлагает автодополнение!
     */
    public function pdo(): \PDO
    {
        if ($this->pdoInstance === null) {
            $config = $this->getConfig('database');
            $this->pdoInstance = new \PDO(
                "mysql:host={$config['host']};dbname=mydb",
                $config['username'] ?? 'root',
                $config['password'] ?? ''
            );
        }
        
        return $this->pdoInstance;
    }
}

// Использование:
$app = App::factory();
$users = $app->pdo()->query('SELECT * FROM users')->fetchAll();
```

### Альтернатива: Строковый DI-контейнер

Если нужна динамическая регистрация сервисов:

```php
$app->addService('cache', new RedisCache());
$app->addService('logger', fn() => new FileLogger('app.log')); // Lazy loading

$cache = $app->getService('cache');
```

---

## 🏗 Архитектура и множественные приложения

Благодаря реестру инстансов на основе `static::class`, вы можете запускать несколько независимых приложений в одном процессе (например, Web и CLI):

```php
class WebApp extends \Arris\App { /* ... */ }
class CliApp extends \Arris\App { /* ... */ }

$web = WebApp::factory();
$cli = CliApp::factory();

// Это два РАЗНЫХ объекта с двумя РАЗНЫМИ конфигами!
var_dump($web === $cli); // bool(false)
```

---

## 🧪 Тестирование

Пакет поставляется с полным набором Unit-тестов (PHPUnit 10).
Для тестирования классов, использующих `App`, просто вызывайте `App::reset()` в `setUp()`:

```php
protected function setUp(): void
{
    \Arris\App::reset(); // Изолирует тесты друг от друга
}
```

Запуск тестов самого пакета:
```bash
./vendor/bin/phpunit
```

---

## 📚 Экосистема Arris

Ядро `karelwintersky/arris` отлично интегрируется с другими пакетами серии:

- 🪵 `karelwintersky/arris.logger` — Продвинутое логирование
- 🛣 `karelwintersky/arris.router` — Быстрый роутер
- 📥 `karelwintersky/arris.php-file-download` — Утилита для скачивания файлов
- 🧰 `karelwintersky/arris.toolkit.mimetypes` — Справочник MIME-типов

---

## 📝 Лицензия

MIT © [Karel Wintersky](https://github.com/KarelWintersky)
