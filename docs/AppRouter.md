# About 

Попытка написать свой роутер на базе https://github.com/nikic/FastRoute

Реализует возможность статического класса AppRouter с методами:

- init()
- get()
- post()
- etc

Пример роутинга:

```php

AppRouter::init(AppLogger::scope('routing'));

AppRouter::get('/', function () {
    CLIConsole::say('Call from /');
}, 'root');

/**
 * ВАЖНО: миддлвары группы не работают, если URL-ы имеют опциональные секции. 
 * 
 * Это происходит потому, что метод dispatch() не возвращает информации о реальном сопоставлении переданного URL и сработавшего правила роутинга.
 * 
 * Переделать это можно, но нужно делать свой форк nikic/fast-route
 * Возможно, нет смысла, и проще взять роутер из slim или взять весь slim
 */

AppRouter::group([
    'prefix' => '/auth', 
    'namespace' => 'Auth', 
    'before' => static function () { CLIConsole::say('Called BEFORE middleware for /auth/*'); }, 
    'after' => null
    ], static function() {

    AppRouter::get('/login', function () {
        CLIConsole::say('Call /auth/login');
    });

    AppRouter::group(['prefix' => '/ajax'], static function() {

        AppRouter::get('/getKey', function (){
            CLIConsole::say('Call from /test/ajax/getKey');
        }, 'auth:ajax:getKey');

    });

    AppRouter::get('/get', function (){
        CLIConsole::say('Call from /test/get (declared after /ajax prefix group');
    });

    AppRouter::group(['prefix' => '/2'], static function() {
        AppRouter::get('/3', function () {
            CLIConsole::say('Call from /test/2/3');
        });
    });

});

AppRouter::get('/root', function (){
    CLIConsole::say('Call from /root (declared after /ajax prefix group ; after /test prefix group)');
});

AppRouter::group([], function (){
    AppRouter::get('/not_group', function () {

    });
});




```


# todo

Реализовать хелпер `router()`: 

```php

// роутер
// 4 аргумента: строка, строка, хэндлер, имя
router('GET', '/', function (){}, 'root'); 

// 3 аргумента: массив, closure, массив от нуля до двух ключей
router(['prefix'=>'/test'], function (){

    // 3-4 аргумента: строка, строка, коллбэк/хэндлер, [строка]
    router('GET', '/ajax', 'Test::ajax');

    // 2-3 аргумента: строка, коллбэк/хэндлер, [строка]
    router('GET /ajaxKeys', 'Test::ajaxKeys');

}, [
    'before'    =>  handler,
    'after'     =>  handler 
]);



```