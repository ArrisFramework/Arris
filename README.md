# Arris µ-Framework

Class collection for some my projects

# Sub-packages

- Core classes
  - [Arris.AppLogger](https://github.com/ArrisFramework/Arris.AppLogger), `composer require karelwintersky/arris.logger`
  - [Arris.AppRouter](https://github.com/ArrisFramework/Arris.AppRouter), `composer require karelwintersky/arris.router`
- Helpers
  - [Arris.Helpers]
- Utils
  - [Arris.PHP_FileDownload](https://github.com/ArrisFramework/Arris.PHP_FileDownload), `composer require karelwintersky/arris.php-file-download`
  - 
- Toolkits
  - [Arris.Toolkit.MimeTypes](https://github.com/ArrisFramework/Arris.Toolkit.MimeTypes), `composer require karelwintersky/arris.toolkit.mimetypes`
  - [Arris.Toolkit.Nginx](https://github.com/KarelWintersky/Arris.Toolkit.Nginx), `composer require karelwintersky/arris.toolkit.nginx`
  - [Arris.Toolkit.Sphinx](https://github.com/KarelWintersky/Arris.Toolkit.Sphinx), `composer require karelwintersky/arris.toolkit.sphinx`

# How to use 

## App - Реестр

```php
$app = App::factory();

$app->set('PDO', new PDO(/* params */));
$app->set(PHPAuth::class, new PHPAuth($pdo, (new PHPAuthConfig())->loadENV('_env')->getConfig() ));
$app->set(Smarty::class, new Smarty());

$app->addService('pdo.main', new PDO());
```

later:

```php
$app = App::factory(); // or ::handle()

$dbc = $app->get('PDO');

// or

$dbc = (App::access())->get('PDO');

// or

$dbc = (App::factory())->getService('pdo.main'); 
```

# CLIConsole

- todo

# DB 

- todo 

# DBPool

- todo

# Hook

- todo

# Utils\Timer

