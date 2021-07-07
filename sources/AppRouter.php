<?php

namespace Arris;

use Arris\Exceptions\AppRouterException;

use FastRoute;
use FastRoute\RouteCollector;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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
    private static $prefix_current;
    
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
                
                if (!is_null($rule['name']))
                    self::$route_names[ $rule['name'] ] = $rule['route'];
            }
        });

        // Fetch method and URI from somewhere
        $routeInfo = (self::$dispatcher)->dispatch(self::$httpMethod, self::$uri);

        // dispatch errors

        if ($routeInfo[0] === FastRoute\Dispatcher::NOT_FOUND)
            throw new AppRouterException("URL " . self::$uri. " not found", 404);

        if ($routeInfo[0] === FastRoute\Dispatcher::METHOD_NOT_ALLOWED)
            throw new AppRouterException("Method not allowed, valid methods are: " . implode(',', $routeInfo[1]), 405);

        list($state, $handler, $method_parameters) = $routeInfo;

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
                throw new AppRouterException("Class {$class} not defined.", 500);
            }

            if (!method_exists($class, $method)) {
                self::$logger->err("Method {$method} not declared at {$class} class.", [ self::$uri, self::$httpMethod, $class ]);
                throw new AppRouterException("Method {$method} not declared at {$class} class", 500);
            }

            $actor = [ new $class, $method ];

        } elseif (strpos($handler, '::')) {
            // static method
            list($class, $method) = explode('::', $handler, 2);

            if (!class_exists($class)){
                self::$logger->error("Class {$class} not defined.", [ self::$uri, self::$httpMethod, $class ]);
                throw new AppRouterException("Class {$class} not defined.", 500);
            }

            if (!method_exists($class, $method)){
                self::$logger->error("Method {$method} not declared at {$class} class", [ self::$uri, self::$httpMethod, $class ]);
                throw new AppRouterException("Method {$method} not declared at {$class} class", 500);
            }

            $actor = [ $class, $method ];

        } else {
            // function
            if (!function_exists($handler)){
                self::$logger->error("Handler function {$handler} not found", [ self::$uri, self::$httpMethod, $handler ]);
                throw new AppRouterException("Handler function {$handler} not found", 500);
            }

            $actor = $handler;
        }

        call_user_func_array($actor, $method_parameters);

        unset($state);
    }

}

# -eof-
