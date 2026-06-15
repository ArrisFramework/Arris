# config()

Альтернатива:

```php
use App\App;
use App\AppFSNews;

// Чтение и запись конфига (статический shortcut)
App::config('app.debug', true);
$debug = App::config('app.debug');

// Чтение из репозитория
$pdo = App::key('pdo');

// Для второго приложения это тоже работает изолированно!
AppFSNews::config('database.host', '10.0.0.5');
```

Поэтому, по совету QWEN я отказываюсь от установки переменных через этот синтаксический сахар и оставляю его
только как геттер для использования в шаблонах.

Старый код (модифицированный под нынешнюю версию) был таким:

```php
if (!function_exists('Arris\config')) {
    /**
     * Глобальный хелпер для конфига.
     * Всегда работает с основным инстансом App\App.
     * Для других приложений (AppFSNews) используйте AppFSNews::config() или $fsnews->getConfig().
     *
     * @param string|array|null $key
     * @param mixed $value
     * @return mixed
     */
    function config(string|array|null $key = null, mixed $value = null): mixed
    {
        $app = App::getInstance();

        // 1. Массовое добавление: config(['db.host' => 'localhost', 'app.debug' => true])
        if (is_array($key)) {
            $app->addConfig($key);
            return true;
        }

        // 2. Установка значения.
        // Используем func_num_args(), чтобы можно было сетить null: config('key', null)
        if (func_num_args() >= 2) {
            $app->setConfig((string)$key, $value);
            return true;
        }

        // 3. Получение всего конфига: config()
        if ($key === null) {
            return $app->getConfig();
        }

        // 4. Получение конкретного ключа: config('db.host')
        return $app->getConfig($key);
    }
}
```

Теперь регистрация в смарти:

```php
$smarty = new Smarty();

// Регистрируем нашу функцию как плагин Smarty
$smarty->registerPlugin('function', 'config', function($params) {
    $key = $params['key'] ?? null;
    $default = $params['default'] ?? null;
    
    return \Arris\config($key, $default);
});
```

И в шаблонах
```html
{* Прямой вызов *}
<title>{config key="app.name"} - {config key="app.version"}</title>

{* С дефолтным значением *}
<p>Environment: {config key="app.mode" default="production"}</p>

{* Или если Smarty поддерживает нативные PHP-функции в тегах *}
{$appName = config('app.name')}
```

или же:
```php
// В контроллере
$smarty->assign('config', [
    'app' => App::config('app'),
    'paths' => App::config('paths'),
]);
```

```html
{* В шаблоне - чистый доступ к массиву, без вызова функций *}
<title>{$config.app.name} - {$config.app.version}</title>
```