<?php

namespace Arris;

use Monolog\Logger;
use FastRoute;
use Exception;

class AppRouter implements AppRouterInterface
{
    /**
     * @var FastRoute\Dispatcher
     */
    private static $dispatcher;

    /**
     * @var array
     */
    private static $rules;

    /**
     * @var string
     */
    private static $default_namespace = '';

    /**
     * @var string
     */
    private static $current_namespace = '';

    /**
     * @var Logger
     */
    private static $logger;

    private static $httpMethod;

    private static $uri;

    public static function init($logger = null, $options = [])
    {
        self::$logger
            = $logger instanceof Logger
            ? $logger
            : (new Logger('null'))->pushHandler(new \Monolog\Handler\NullHandler());

        self::$httpMethod = $_SERVER['REQUEST_METHOD'];

        $uri = $_SERVER['REQUEST_URI'];
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        self::$uri = rawurldecode($uri);

        if (array_key_exists('defaultNamespace', $options)) {
            self::setDefaultNamespace($options['defaultNamespace']);
        }

    }

    public static function setDefaultNamespace(string $namespace = '')
    {
        self::$default_namespace = $namespace;
        self::$current_namespace = $namespace;
    }

    public static function get($route, $handler)
    {
        self::$rules[] = [
            'httpMethod'    =>  'GET',
            'route'         =>  $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace
        ];
    }

    public static function post($route, $handler)
    {
        self::$rules[] = [
            'httpMethod'    =>  'POST',
            'route'         =>  $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace
        ];
    }

    public static function put($route, $handler)
    {
        self::$rules[] = [
            'httpMethod'    =>  'PUT',
            'route'         =>  $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace
        ];
    }

    public static function patch($route, $handler)
    {
        self::$rules[] = [
            'httpMethod'    =>  'PATCH',
            'route'         =>  $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace
        ];
    }

    public static function delete($route, $handler)
    {
        self::$rules[] = [
            'httpMethod'    =>  'DELETE',
            'route'         =>  $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace
        ];
    }

    public static function head($route, $handler)
    {
        self::$rules[] = [
            'httpMethod'    =>  'HEAD',
            'route'         =>  $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace
        ];
    }

    public static function addRoute($httpMethod, $route, $handler)
    {
        foreach ((array) $httpMethod as $method) {
            self::$rules[] = [
                'httpMethod'    =>  $method,
                'route'         =>  $route,
                'handler'       =>  $handler,
                'namespace'     =>  self::$current_namespace
            ];
        }
    }

    public static function groupNamespace($namespace, callable $callback)
    {
        self::$current_namespace = $namespace;
        $callback();
        self::$current_namespace = self::$default_namespace;
    }

    public static function dispatch()
    {
        $dispatcher = FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $r) {
            foreach (self::$rules as $rule) {
                $r->addRoute($rule['httpMethod'], $rule['route'], $rule['handler']);
            }
        });

        // Fetch method and URI from somewhere
        $routeInfo = $dispatcher->dispatch(self::$httpMethod, self::$uri);

        // dispatch errors

        if ($routeInfo[0] === FastRoute\Dispatcher::NOT_FOUND)
            throw new Exception("URL " . self::$uri. " not found", 404);

        if ($routeInfo[0] === FastRoute\Dispatcher::METHOD_NOT_ALLOWED)
            throw new Exception("Method not allowed, valid methods are: " . implode(',', $routeInfo[1]), 405);

        list($state, $handler, $method_parameters) = $routeInfo;

        $handler = (self::$default_namespace != '') ? self::$default_namespace . "\\{$handler}" : $handler;

        if (strpos($handler, '@') > 0) {
            // dynamic method
            list($class, $method) = explode('@', $handler, 2);

            if (!class_exists($class)) {
                self::$logger->error("Class {$class} not defined.", [ self::$uri, self::$httpMethod, $class ]);
                throw new Exception("Class {$class} not defined.", 500);
            }

            if (!method_exists($class, $method)) {
                self::$logger->err("Method {$method} not declared at {$class} class.", [ self::$uri, self::$httpMethod, $class ]);
                throw new Exception("Method {$method} not declared at {$class} class", 500);
            }

            $actor = [ new $class, $method ];

        } elseif (strpos($handler, '::')) {
            // static method
            list($class, $method) = explode('::', $handler, 2);

            if (!class_exists($class)){
                self::$logger->error("Class {$class} not defined.", [ self::$uri, self::$httpMethod, $class ]);
                throw new Exception("Class {$class} not defined.", 500);
            }

            if (!method_exists($class, $method)){
                self::$logger->error("Method {$method} not declared at {$class} class", [ self::$uri, self::$httpMethod, $class ]);
                throw new Exception("Method {$method} not declared at {$class} class", 500);
            }

            $actor = [ $class, $method ];

        } else {
            // function
            if (!function_exists($handler)){
                self::$logger->error("Handler function {$handler} not found", [ self::$uri, self::$httpMethod, $handler ]);
                throw new Exception("Handler function {$handler} not found", 500);
            }

            $actor = $handler;
        }

        call_user_func_array($actor, $method_parameters);

        unset($state);
    }

}

# -eof-
