<?php

namespace Arris;

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

    private static $httpMethod;

    private static $uri;

    private static $backup_options = [
        'prefix'        =>  '',
        'namespace'     =>  '',
        'middleware'    =>  \stdClass::class //@todo ???
    ];

    /**
     * @var
     */
    private static $route_names;

    /**
     * @var
     */
    private static $prefix_current;

    /**
     * Current Routing Info
     *
     * @var array
     */
    private static $routeInfo;
    
    /**
     * @var array
     */
    private static $error_handlers;
    
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
        }

    }

    public static function setDefaultNamespace(string $namespace = '')
    {
        self::$default_namespace = $namespace;
        self::$current_namespace = $namespace;
    }

    public static function get($route, $handler, $name = null)
    {
        self::$rules[] = [
            'httpMethod'    =>  'GET',
            'route'         =>  $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name
        ];
    }

    public static function post($route, $handler, $name = null)
    {
        self::$rules[] = [
            'httpMethod'    =>  'POST',
            'route'         =>  $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name
        ];
    }

    public static function put($route, $handler, $name = null)
    {
        self::$rules[] = [
            'httpMethod'    =>  'PUT',
            'route'         =>  $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name
        ];
    }

    public static function patch($route, $handler, $name = null)
    {
        self::$rules[] = [
            'httpMethod'    =>  'PATCH',
            'route'         =>  $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name
        ];
    }

    public static function delete($route, $handler, $name = null)
    {
        self::$rules[] = [
            'httpMethod'    =>  'DELETE',
            'route'         =>  $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name
        ];
    }

    public static function head($route, $handler, $name = null)
    {
        self::$rules[] = [
            'httpMethod'    =>  'HEAD',
            'route'         =>  $route,
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
                'route'         =>  $route,
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

    public static function group(array $options, callable $callback)
    {
        $_setPrefix = array_key_exists('prefix', $options);
        $_setNamespace = array_key_exists('namespace', $options);

        if ($_setPrefix) {
            self::$backup_options['prefix'] = self::$prefix_current;
            self::$prefix_current = $options['prefix'];
        }

        if ($_setNamespace) {
            self::$backup_options['namespace'] = self::$current_namespace;
            self::$current_namespace = $options['namespace'];
        }

        $callback();

        if ($_setNamespace) {
            self::$current_namespace = self::$backup_options['namespace'];
        }

        if ($_setPrefix) {
            self::$prefix_current = self::$backup_options['prefix'];
        }
    }

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

    public static function dispatch()
    {
        self::$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
            foreach (self::$rules as $rule) {
                $handler
                    = (is_string($rule['handler']) && !empty($rule['namespace']))
                    ? "{$rule['namespace']}\\{$rule['handler']}"
                    : $rule['handler'];
                
                $r->addRoute($rule['httpMethod'], $rule['route'], $handler);
                
                if (!is_null($rule['name'])) {
                    self::$route_names[$rule['name']] = $rule['route'];
                }
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
     * @inheritDoc
     */
    public static function getRoutingRules(): array
    {
        return self::$rules;
    }

    /**
     * А зачем я этот метод делал?
     *
     * @param $code
     * @param callable $callable
     * @return void
     */
    public static function setErrorHandler($code, callable $callable)
    {
        self::$error_handlers[$code] = $callable;
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
