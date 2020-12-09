# Основной класс App

Главная функция: реестр.

Пример использования:

```
App::set(DB::class, DB::C());
App::set(PHPAuth::class, new PHPAuth(DB::C(), (new PHPAuthConfig())->loadENV('_env')->getConfig() ));
```

```
$dbc = App::get(DB::class);

$auth = App::get(PHPAuth::class);
```