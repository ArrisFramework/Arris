 # Arris µ-Framework

Class collection for some my projects

# Sub-packages

The Arris ecosystem is split into separate composer packages; each package is a
repository under `/var/www.arris/` and ships its own `AGENTS.md`. Install the ones
you need: `composer require karelwintersky/<package>`.

## Core

This package — [arris](https://github.com/KarelWintersky/Arris) — the framework core itself:
`App` singleton, `AppErrorHandler`, `Hook` events, `Controllers\AbstractController`,
global helpers, `Core\Dot`, PSR-16 compatible cache.

Companion core packages:

- [arris.config](https://github.com/ArrisFramework/Arris.Config) — config reader/writer, `AppConfig`. `composer require karelwintersky/arris.config`
- [arris.entity](https://github.com/ArrisFramework/Arris.Entity) — entity types for the framework. `composer require karelwintersky/arris.entity`
- [arris.entity.path](https://github.com/ArrisFramework/Arris.Entity.Path) — path builder. `composer require karelwintersky/arris.entity.path`
- [arris.entity.url](https://github.com/ArrisFramework/Arris.Entity.URL) — URL builder. `composer require karelwintersky/arris.entity.url`
- [arris.logger](https://github.com/KarelWintersky/Arris.AppLogger) — application logger. `composer require karelwintersky/arris.logger`
- [arris.router](https://github.com/ArrisFramework/Arris.AppRouter) — application router. `composer require karelwintersky/arris.router`
- [arris.cache](https://github.com/ArrisFramework/Arris.Cache) — cache engine. `composer require karelwintersky/arris.cache`
- arris.catcher — exception catcher: error dump with call hierarchy or a safe stub. `composer require karelwintersky/arris.catcher`
- [arris.presenter](https://github.com/ArrisFramework/Arris.Template) — presenter with lazy Smarty wrapper. `composer require karelwintersky/arris.presenter`

## Toolkits

- [arris.toolkit.cli-console](https://github.com/KarelWintersky/Arris.Toolkit.CLIConsole) — CLI console helper. `composer require karelwintersky/arris.toolkit.cli-console`
- [arris.toolkit.firewall](https://github.com/ArrisFramework/Arris.Toolkit.Firewall) — IP filtering. `composer require karelwintersky/arris.toolkit.firewall`
- [arris.toolkit.mimetypes](https://github.com/ArrisFramework/Arris.Toolkit.MimeTypes) — MimeType ↔ extension resolver. `composer require karelwintersky/arris.toolkit.mimetypes`
- [arris.toolkit.nanoredis](https://github.com/ArrisFramework/Arris.Toolkit.NanoRedis) — minimal Redis client. `composer require karelwintersky/arris.toolkit.nanoredis`
- [arris.php-file-download](https://github.com/ArrisFramework/Arris.Toolkit.FileDownload) — file download helper. `composer require karelwintersky/arris.php-file-download`
- [arris.php-file-upload](https://github.com/ArrisFramework/Arris.Toolkit.FileUpload) — file upload with validation and conversion. `composer require karelwintersky/arris.php-file-upload`


```php
// Добавление в конфиг
App::factory()->addConfig([
    'smarty'    =>  [
        'path_template' =>  self::$path_install->join('templates'),
        'path_cache'    =>  self::$path_install->join('cache')
    ]
]);
```

# Global helpers `config()` / `app()`

Helpers `config()` and `app()` are not hard-bound to `Arris\App` — they use the
application class registered in the framework. This matters when your project has
its own app class extending `Arris\App` (e.g. `App\App`): without registration the
helpers would build a *fresh* `Arris\App` instance instead of touching yours.

Register your class once at bootstrap, before any helper call:

```php
use Arris\App;
use App\App as MyApp;

MyApp::setApplicationClass(MyApp::class);
// ... или как зарегистрированный инстанс-класс
App::setApplicationClass(MyApp::class);

config('database.host');  // теперь читает конфиг MyApp-инстанса
app();                    // возвращает MyApp-инстанс (соблюдение singleton)
```

The given class must extend `Arris\App`, otherwise `RuntimeException` is thrown.
If nothing was registered (`setApplicationClass()` was not called) the helpers fall
back to `Arris\App`.