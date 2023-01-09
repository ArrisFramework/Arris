<?php

/**
 * Тестер класса AppRouter
 *
 * Использует конфиг nginx:

server {
    listen 80;
    server_name router.local;

    root /var/www.arris/Arris/_tests/;

    index index.php index.html;

    access_log /var/www.arris/Arris/~access.log;
    error_log /var/www.arris/Arris/~error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include         fastcgi_params;
        fastcgi_param   SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass    php-handler-7-4;
        fastcgi_index   index.php;
    }

    location ~ favicon.* {
        access_log      off;
        log_not_found   off;
    }
}

 */

const PATH_ENV = '/etc/ajur/47news/';

use Arris\App;
use Arris\AppLogger;
use Arris\AppRouter;
use Arris\CLIConsole;
use Arris\Exceptions\AppRouterException;
use Arris\Exceptions\AppRouterHandlerError;
use Arris\Exceptions\AppRouterMethodNotAllowedException;
use Arris\Exceptions\AppRouterNotFoundException;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

if (!function_exists('config')) {
    function config($key = '', $value = null) {
        $app = App::factory();

        if (!is_null($value) && !empty($key)) {
            $app->setConfig($key, $value);
            return true;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $app->setConfig($k, $v);
            }
            return true;
        }

        if (empty($key)) {
            return $app->getConfig();
        }

        return $app->getConfig($key);
    }
}

try {
    foreach (['site_admin.conf', 'credentials.conf', 'common.conf', 'features.conf', 'logging.conf'] as $file) {
        Dotenv::create(PATH_ENV, $file)->load();
    }

    AppRouter::init(AppLogger::scope('routing'));

    /*

    /
    /test
    /test/ajax/getKey
    /test/get
    /root


     */
    AppRouter::get('/', function () {
        CLIConsole::say('Call from /');
    }, 'root');

    // helper:

    function router(){}

    // роутер
    router('GET', '/', function (){}, 'root'); // 4 аргумента

    // 3 аргумента: массив, коллбэк, имя группы, опциональное
    router(['prefix'=>'/test'], function (){

        // 3 аргумента: строка, строка, коллбэк/хэндлер, [строка]
        router('GET', '/ajax', 'Test::ajax');

        // 2 аргумента: строка, коллбэк/хэндлер, [строка]
        router('GET /ajaxKeys', 'Test::ajaxKeys');

    }, 'testgroup');


    /**
     * @todo: миддлвары группы не работают, если URL-ы имеют опциональные секции. Это происходит потому, что метод dispatch() не возвращает информации о реальном сопоставлении переданного URL и сработавшего правила роутинга.
     *
     */
    AppRouter::group(
        [
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

    CLIConsole::say('<hr>');

    // Sage::$expandedByDefault = true;

    // !sage( AppRouter::getRoutingRules());

    sage( AppRouter::getRouter('auth:ajax:getKey') );

    // CLIConsole::say('<hr>Result<hr>');

    AppRouter::dispatch();

} catch (AppRouterMethodNotAllowedException $e) {
    d($e->getMessage());
} catch (AppRouterNotFoundException $e) {
    sage($e->getMessage());
} catch (AppRouterHandlerError|AppRouterException $e) {
    d($e->getMessage());
} catch (FastRoute\BadRouteException $e){
    d($e->getMessage());
} catch (RuntimeException $e) {
}
