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