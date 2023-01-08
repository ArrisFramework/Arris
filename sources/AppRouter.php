<?php

namespace Arris;

use Arris\Core\Stack;
use Arris\Exceptions\AppRouterHandlerError;
use Arris\Exceptions\AppRouterMethodNotAllowedException;
use Arris\Exceptions\AppRouterNotFoundException;

use FastRoute;
use FastRoute\RouteCollector;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class AppRouter implements AppRouterInterface
{
    public const ALL_HTTP_METHODS = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'HEAD'
    ];

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
     * @var LoggerInterface
     */
    private static $logger;

    /**
     * @var
     */
    private static $httpMethod;

    /**
     * @var string
     */
    private static $uri;

    private static $backup_options = [
        'prefix'        =>  '',
        'namespace'     =>  '',
        'middleware'    =>  \stdClass::class //@todo ???
    ];

    /**
     * @var array
     */
    public static $route_names;

    /**
     * @var string
     */
    private static $current_prefix;

    /**
     * Current Routing Info
     *
     * @var array
     */
    private static array $routeInfo;
    
    private static Stack $stack_prefix;

    private static Stack $stack_namespace;
    
    public static function init(LoggerInterface $logger = null, $options = [])
    {
        self::$logger
            = (!is_null($logger) && $logger instanceof LoggerInterface)
            ? $logger
            : new NullLogger();

        self::$httpMethod = $_SERVER['REQUEST_METHOD'];

        $uri = $_SERVER['REQUEST_URI'];
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        self::$uri = rawurldecode($uri);

        if (array_key_exists('defaultNamespace', $options)) {
            self::setDefaultNamespace($options['defaultNamespace']);
        } elseif (array_key_exists('namespace', $options)) {
            self::setDefaultNamespace($options['namespace']);
        }

        if (array_key_exists('prefix', $options)) {
            self::$current_prefix = $options['prefix'];
        }

        self::$stack_prefix = new Stack();

        self::$stack_namespace = new Stack();
    }

    public static function setDefaultNamespace(string $namespace = '')
    {
        self::$default_namespace = $namespace;
        self::$current_namespace = $namespace;
    }

    public static function get($route, $handler, $name = null)
    {
        if (!is_null($name)) {
            self::$route_names[$name] = $route;
        }

        self::$rules[] = [
            'httpMethod'    =>  'GET',
            'route'         =>  self::$current_prefix . $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name
        ];
    }

    public static function post($route, $handler, $name = null)
    {
        self::$rules[] = [
            'httpMethod'    =>  'POST',
            'route'         =>  self::$current_prefix . $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name
        ];
    }

    public static function put($route, $handler, $name = null)
    {
        self::$rules[] = [
            'httpMethod'    =>  'PUT',
            'route'         =>  self::$current_prefix . $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name
        ];
    }

    public static function patch($route, $handler, $name = null)
    {
        self::$rules[] = [
            'httpMethod'    =>  'PATCH',
            'route'         =>  self::$current_prefix . $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name
        ];
    }

    public static function delete($route, $handler, $name = null)
    {
        self::$rules[] = [
            'httpMethod'    =>  'DELETE',
            'route'         =>  self::$current_prefix . $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name
        ];
    }

    public static function head($route, $handler, $name = null)
    {
        self::$rules[] = [
            'httpMethod'    =>  'HEAD',
            'route'         =>  self::$current_prefix . $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name
        ];
    }


    public static function any($route, $handler, $name = null)
    {
        foreach (self::ALL_HTTP_METHODS as $method) {
            self::$rules[] = [
                'httpMethod'    =>  $method,
                'route'         =>  self::$current_prefix . $route,
                'handler'       =>  $handler,
                'namespace'     =>  self::$current_namespace,
                'name'          =>  $name
            ];
        }

        /*self::$rules[] = [
            'httpMethod'    =>  '*',
            'route'         =>  $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name
        ];*/
    }


    public static function addRoute($httpMethod, $route, $handler, $name = null)
    {
        foreach ((array) $httpMethod as $method) {
            self::$rules[] = [
                'httpMethod'    =>  $method,
                'route'         =>  $route,
                'handler'       =>  $handler,
                'namespace'     =>  self::$current_namespace,
                'name'          =>  $name
            ];
        }
    }

    public static function groupNamespace($namespace, callable $callback)
    {
        self::$current_namespace = $namespace;
        $callback();
        self::$current_namespace = self::$default_namespace;
    }

    public static function group(array $options = [], callable $callback = null, string $name = '')
    {
        $_setPrefix = array_key_exists('prefix', $options);
        $_setNamespace = array_key_exists('namespace', $options);

        if ($_setPrefix) {
            self::$stack_prefix->push($options['prefix']);
            self::$current_prefix = self::$stack_prefix->implode();
        }

        if ($_setNamespace) {
            self::$stack_namespace->push($options['namespace']);
            self::$current_namespace = self::$stack_namespace->implode('\\');
        }

        $callback();

        if ($_setNamespace) {
            self::$stack_namespace->pop();
            self::$current_namespace = self::$stack_namespace->implode('\\');
        }

        if ($_setPrefix) {
            self::$stack_prefix->pop();
            self::$current_prefix = self::$stack_prefix->implode();
        }
    }

    /**
     * Возвращает информацию о роуте по имени
     *
     * Сейчас работает только ПОСЛЕ dispatch
     *
     * @param string $name
     * @return string
     */
    public static function getRouter($name = '')
    {
        if ($name === '') {
            return '/';
        }

        if (array_key_exists($name, self::$route_names)) {
            return self::$route_names[ $name ];
        }
    
        return '/';
    }

    /**
     * Не делает нихрена
     *
     * @return array
     */
    public static function getRoutersNames()
    {
        return self::$route_names;
    }

    public static function dispatch()
    {
        self::$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
            foreach (self::$rules as $rule) {
                $handler
                    = (is_string($rule['handler']) && !empty($rule['namespace']))
                    ? "{$rule['namespace']}\\{$rule['handler']}"
                    : $rule['handler'];

                sage($handler);
                
                $r->addRoute($rule['httpMethod'], $rule['route'], $handler);

                /*if (!is_null($rule['name'])) {
                    self::$route_names[$rule['name']] = $rule['route'];
                }*/
            }
        });

        // Fetch method and URI from somewhere
        self::$routeInfo = $routeInfo = (self::$dispatcher)->dispatch(self::$httpMethod, self::$uri);

        list($state, $handler, $method_parameters) = $routeInfo;

        // dispatch errors
        if ($state === FastRoute\Dispatcher::NOT_FOUND) {
            throw new AppRouterNotFoundException(self::jsonize([
                'message'   =>  "URL " . self::$uri . " not found",
                'uri'       =>  self::$uri
            ]), 404);
        }

        if ($state === FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
            throw new AppRouterMethodNotAllowedException(self::jsonize([
                'uri'       => self::$uri,
                'method'    => self::$httpMethod,
                'info'      => self::$routeInfo
            ]), 405);
        }

        //@todo: namespace

        /*$handler
            = (
            is_string($handler) && self::$default_namespace != '' )
            ? self::$default_namespace . "\\{$handler}"
            : $handler;
        */

        if ($handler instanceof \Closure) {
            $actor = $handler;
        } elseif (strpos($handler, '@') > 0) {
            // dynamic method
            list($class, $method) = explode('@', $handler, 2);

            if (!class_exists($class)) {
                self::$logger->error("Class {$class} not defined.", [ self::$uri, self::$httpMethod, $class ]);
                throw new AppRouterHandlerError(self::jsonize([
                    'message'   =>  "Class {$class} not defined",
                    'uri'       =>  self::$uri,
                    'method'    =>  self::$httpMethod,
                    'info'      =>  self::$routeInfo
                ]), 500);
            }

            if (!method_exists($class, $method)) {
                self::$logger->error("Method {$method} not declared at {$class} class.", [ self::$uri, self::$httpMethod, $class ]);
                throw new AppRouterHandlerError(self::jsonize([
                    'message'   =>  "Method {$method} not declared at {$class} class",
                    'uri'       =>  self::$uri,
                    'method'    =>  self::$httpMethod,
                    'info'      =>  self::$routeInfo
                ]), 500);
            }

            $actor = [ new $class, $method ];

        } elseif (strpos($handler, '::')) {
            // static method
            list($class, $method) = explode('::', $handler, 2);

            if (!class_exists($class)){
                self::$logger->error("Class {$class} not defined.", [ self::$uri, self::$httpMethod, $class ]);
                throw new AppRouterHandlerError(self::jsonize([
                    'message'   =>  "Class {$class} not defined",
                    'uri'       =>  self::$uri,
                    'method'    =>  self::$httpMethod,
                    'info'      =>  self::$routeInfo
                ]), 500);
            }

            if (!method_exists($class, $method)){
                self::$logger->error("Method {$method} not declared at {$class} class", [ self::$uri, self::$httpMethod, $class ]);
                throw new AppRouterHandlerError(self::jsonize([
                    'message'   =>  "Method {$method} not declared at {$class} class",
                    'uri'       =>  self::$uri,
                    'method'    =>  self::$httpMethod,
                    'info'      =>  self::$routeInfo
                ]), 500);
            }

            $actor = [ $class, $method ];

        } else {
            // function
            if (!function_exists($handler)){
                self::$logger->error("Handler function {$handler} not found", [ self::$uri, self::$httpMethod, $handler ]);
                throw new AppRouterHandlerError(self::jsonize([
                    'message'   =>  "Handler function {$handler} not found",
                    'uri'       =>  self::$uri,
                    'method'    =>  self::$httpMethod,
                    'info'      =>  self::$routeInfo
                ]), 500);
            }

            $actor = $handler;
        }

        call_user_func_array($actor, $method_parameters);

        unset($state);
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    public static function getRoutingInfo(): array
    {
        return self::$routeInfo;
    }

    /**
     * Возвращает список объявленных роутов: [ 'method route' => [ handler, namespace, name ]
     *
     * @return array
     */
    public static function getRoutingRules(): array
    {
        $rules = [];
        foreach (self::$rules as $record) {
            $key = "{$record['httpMethod']} {$record['route']}";

            if (array_key_exists($key, $rules)) {
                $key .= " [ DUPLICATE ROUTE " . microtime(false) . ' ]';
            }

            $rules[ $key ] = [
                'handler'   =>  is_callable($record['handler']) ? "Closure" : $record['handler'],
                'namespace' =>  $record['namespace'],
                'name'      =>  $record['name'],
            ];
        }

        return $rules;
    }

    /**
     * @throws \JsonException
     */
    private static function jsonize($data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
    }

}

# -eof-
