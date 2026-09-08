 # Arris µ-Framework

Class collection for some my projects

# Sub-packages

- Core classes
  - [Arris.AppLogger](https://github.com/ArrisFramework/Arris.AppLogger), `composer require karelwintersky/arris.logger`
  - [Arris.AppRouter](https://github.com/ArrisFramework/Arris.AppRouter), `composer require karelwintersky/arris.router`
- Toolkits
  - [Arris.Toolkit.MimeTypes](https://github.com/ArrisFramework/Arris.Toolkit.MimeTypes), `composer require karelwintersky/arris.toolkit.mimetypes`
  - [Arris.Toolkit.Nginx](https://github.com/KarelWintersky/Arris.Toolkit.Nginx), `composer require karelwintersky/arris.toolkit.nginx`
  - [Arris.Toolkit.Sphinx](https://github.com/KarelWintersky/Arris.Toolkit.Sphinx), `composer require karelwintersky/arris.toolkit.sphinx`


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