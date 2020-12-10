# Основной класс App

Главная функция: реестр.

Пример использования:

```
$app = App::access();  // or ::handle()

$app->set(DB::class, DB::C());
$app->set(PHPAuth::class, new PHPAuth(DB::C(), (new PHPAuthConfig())->loadENV('_env')->getConfig() ));
```

```
$app = App::access(); // or ::handle()

$dbc = $app->get(DB::class);

// or

$dbc = (App::access())->get(DB::class); 

$auth = $app->get(PHPAuth::class);
```