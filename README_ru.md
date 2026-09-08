# Arris µ-Framework

Коллекция классов для моих проектов

# Подпакеты

Экосистема Arris разбита на отдельные composer-пакеты; каждый пакет — репозиторий
в `/var/www.arris/` со своим `AGENTS.md`. Ставятся все одинаково:
`composer require karelwintersky/<пакет>`.

## Ядро

Этот пакет — [arris](https://github.com/KarelWintersky/Arris) — само ядро фреймворка:
singleton `App`, `AppErrorHandler`, события `Hook`, `Controllers\AbstractController`,
глобальные хелперы, `Core\Dot`, PSR-16-совместимый кэш.

Сопутствующие пакеты ядра:

- [arris.config](https://github.com/ArrisFramework/Arris.Config) — чтение/запись конфига, класс `AppConfig`. `composer require karelwintersky/arris.config`
- [arris.entity](https://github.com/ArrisFramework/Arris.Entity) — типы-сущности для фреймворка. `composer require karelwintersky/arris.entity`
- [arris.entity.path](https://github.com/ArrisFramework/Arris.Entity.Path) — построитель путей. `composer require karelwintersky/arris.entity.path`
- [arris.entity.url](https://github.com/ArrisFramework/Arris.Entity.URL) — построитель URL. `composer require karelwintersky/arris.entity.url`
- [arris.logger](https://github.com/KarelWintersky/Arris.AppLogger) — логгер приложения. `composer require karelwintersky/arris.logger`
- [arris.router](https://github.com/ArrisFramework/Arris.AppRouter) — роутер приложения. `composer require karelwintersky/arris.router`
- [arris.cache](https://github.com/ArrisFramework/Arris.Cache) — кэш-движок. `composer require karelwintersky/arris.cache`
- arris.catcher — ловец исключений: дамп ошибки с иерархией вызовов либо безопасная заглушка. `composer require karelwintersky/arris.catcher`
- [arris.presenter](https://github.com/ArrisFramework/Arris.Template) — презентер с ленивой обёрткой над Smarty. `composer require karelwintersky/arris.presenter`

## Инструменты (Toolkits)

- [arris.toolkit.cli-console](https://github.com/KarelWintersky/Arris.Toolkit.CLIConsole) — хелпер для CLI-консоли. `composer require karelwintersky/arris.toolkit.cli-console`
- [arris.toolkit.firewall](https://github.com/ArrisFramework/Arris.Toolkit.Firewall) — фильтрация по IP. `composer require karelwintersky/arris.toolkit.firewall`
- [arris.toolkit.mimetypes](https://github.com/ArrisFramework/Arris.Toolkit.MimeTypes) — MimeType ↔ расширение. `composer require karelwintersky/arris.toolkit.mimetypes`
- [arris.toolkit.nanoredis](https://github.com/ArrisFramework/Arris.Toolkit.NanoRedis) — минимальный Redis-клиент. `composer require karelwintersky/arris.toolkit.nanoredis`
- [arris.php-file-download](https://github.com/ArrisFramework/Arris.Toolkit.FileDownload) — хелпер выдачи файлов на скачивание. `composer require karelwintersky/arris.php-file-download`
- [arris.php-file-upload](https://github.com/ArrisFramework/Arris.Toolkit.FileUpload) — загрузка файлов с валидацией и конвертацией. `composer require karelwintersky/arris.php-file-upload`


```php
// Добавление в конфиг
App::factory()->addConfig([
    'smarty'    =>  [
        'path_template' =>  self::$path_install->join('templates'),
        'path_cache'    =>  self::$path_install->join('cache')
    ]
]);
```

# Глобальные хелперы `config()` / `app()`

Хелперы `config()` и `app()` не привязаны жёстко к `Arris\App` — они работают
с классом приложения, зарегистрированным во фреймворке. Это важно, когда в проекте
есть свой класс приложения, расширяющий `Arris\App` (например, `App\App`): без
регистрации хелперы создали бы *новый* инстанс `Arris\App` вместо того, чтобы
обращаться к вашему.

Зарегистрируйте свой класс один раз, на этапе загрузки приложения, до вызова хелперов:

```php
use Arris\App;
use App\App as MyApp;

MyApp::setApplicationClass(MyApp::class);
// ... или через инстанс-класс
App::setApplicationClass(MyApp::class);

config('database.host');  // теперь читает конфиг инстанса MyApp
app();                    // возвращает инстанс MyApp (соблюдение singleton)
```

Переданный класс должен расширять `Arris\App`, иначе будет брошен `RuntimeException`.
Если регистрации не было (`setApplicationClass()` не вызывался), хелперы
используют `Arris\App`.