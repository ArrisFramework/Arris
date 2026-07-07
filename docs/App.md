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

## 📖 API — Репозиторий опций

Инстанс `App` предоставляет репозиторий для хранения произвольных данных (опций).

```php
$app = App::getInstance();

// Запись
$app->set('db.host', 'localhost');
$app->add(['db.port' => 3306, 'db.name' => 'test']); // массовое добавление

// Чтение
$host = $app->get('db.host');              // 'localhost'
$port = $app->get('db.port', 5432);        // с дефолтом
$all  = $app->all();                       // весь массив опций

// Проверка и удаление
$app->has('db.host');                      // true
$app->remove('db.host');                   // удалить ключ

// Fluent-чейнинг
$app->add(['a' => 1])->set('b', 2)->get('a'); // 1

// Магические методы (те же опции)
$app->magicProp = 'value';
echo $app->magicProp;                      // 'value'
isset($app->magicProp);                    // true
```

### Статический shortcut

```php
App::key('db.host'); // аналог App::getInstance()->get('db.host')
```

---

## 📖 API — Конфигурация

Управление конфигом приложения (отдельный слой, изолированный от опций).

```php
// Чтение и запись
$app->setConfig('app.debug', true);
$debug = $app->getConfig('app.debug');        // true
$app->getConfig('app.missing', 'default');     // 'default'

// Слияние
$app->addConfig(['app' => ['version' => '1.0']]);

// Замена целиком
$app->replaceConfig(['new' => 'config']);

// Проверка, удаление, дамп
$app->hasConfig('app.debug');                 // true
$app->removeConfig('app.debug');              // установить в null
$all = $app->allConfig();                     // весь конфиг массивом

// Fluent
$app->addConfig(['a' => 1])->setConfig('b', 2);
```

### Статические shortcuts

```php
App::config();                    // весь конфиг (объект AppConfig)
App::config('app.debug');        // get
App::config('app.debug', true);  // set (внимание: 2 аргумента = set)
App::config('app.debug', default: false); // get с дефолтом

App::fromConfig('app.debug', false); // get с дефолтом (без путаницы с set)
App::toConfig('app.debug', true);    // set
```

> ⚠️ `config()` с 2 аргументами работает как set. Чтобы получить значение с дефолтом, используйте именованный аргумент: `config('key', default: 'val')`.

---

## 📖 API — PSR-11 Service Container

`App` — это сервис-локатор (DI), но его метод `get(?string, mixed)` несовместим с `Psr\Container\ContainerInterface::get(string $id)`.  
Для PSR-11-совместимости используется адаптер `Arris\Core\Container\ServiceContainer`:

```php
use Arris\Core\Container\ServiceContainer;

$container = new ServiceContainer(); // адаптирует App::getInstance()
$container = new ServiceContainer($customAppInstance);

$container->has('db');               // true/false
$container->get('db');               // сервис или ContainerNotFoundException
```

Адаптер делегирует в `isService()` / `getService()` и пробрасывает `ContainerNotFoundException` при отсутствии ключа.

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
