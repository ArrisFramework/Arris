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

    private static Stack $stack_middlewares_before;

    private static $current_middleware_before = null;

    private static Stack $stack_middlewares_after;

    private static $current_middleware_after = null;
    
    public static function init(LoggerInterface $logger = null, array $options = [])
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

        self::$stack_middlewares_before = new Stack();

        self::$stack_middlewares_after = new Stack();
    }

    public static function setDefaultNamespace(string $namespace = '')
    {
        self::$current_namespace = $namespace;
    }

    public static function get($route, $handler, $name = null)
    {
        if (!is_null($name)) {
            self::$route_names[$name] = self::$current_prefix . $route;
        }

        self::$rules[] = [
            'httpMethod'    =>  'GET',
            'route'         =>  self::$current_prefix . $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name,
            'middlewares'   =>  [
                'before'        =>  self::$current_middleware_before,
                'after'         =>  self::$current_middleware_after
            ]
        ];
    }

    public static function post($route, $handler, $name = null)
    {
        if (!is_null($name)) {
            self::$route_names[$name] = self::$current_prefix . $route;
        }

        self::$rules[] = [
            'httpMethod'    =>  'POST',
            'route'         =>  self::$current_prefix . $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name,
            'middlewares'   =>  [
                'before'        =>  self::$current_middleware_before,
                'after'         =>  self::$current_middleware_after
            ]
        ];
    }

    public static function put($route, $handler, $name = null)
    {
        if (!is_null($name)) {
            self::$route_names[$name] = self::$current_prefix . $route;
        }

        self::$rules[] = [
            'httpMethod'    =>  'PUT',
            'route'         =>  self::$current_prefix . $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name,
            'middlewares'   =>  [
                'before'        =>  self::$current_middleware_before,
                'after'         =>  self::$current_middleware_after
            ]
        ];
    }

    public static function patch($route, $handler, $name = null)
    {
        if (!is_null($name)) {
            self::$route_names[$name] = self::$current_prefix . $route;
        }

        self::$rules[] = [
            'httpMethod'    =>  'PATCH',
            'route'         =>  self::$current_prefix . $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name,
            'middlewares'   =>  [
                'before'        =>  self::$current_middleware_before,
                'after'         =>  self::$current_middleware_after
            ]
        ];
    }

    public static function delete($route, $handler, $name = null)
    {
        if (!is_null($name)) {
            self::$route_names[$name] = self::$current_prefix . $route;
        }

        self::$rules[] = [
            'httpMethod'    =>  'DELETE',
            'route'         =>  self::$current_prefix . $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name,
            'middlewares'   =>  [
                'before'        =>  self::$current_middleware_before,
                'after'         =>  self::$current_middleware_after
            ]
        ];
    }

    public static function head($route, $handler, $name = null)
    {
        if (!is_null($name)) {
            self::$route_names[$name] = self::$current_prefix . $route;
        }

        self::$rules[] = [
            'httpMethod'    =>  'HEAD',
            'route'         =>  self::$current_prefix . $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name,
            'middlewares'   =>  [
                'before'        =>  self::$current_middleware_before,
                'after'         =>  self::$current_middleware_after
            ]
        ];
    }


    public static function any($route, $handler, $name = null)
    {
        foreach (self::ALL_HTTP_METHODS as $method) {
            if (!is_null($name)) {
                self::$route_names[$name] = self::$current_prefix . $route;
            }

            self::$rules[] = [
                'httpMethod'    =>  $method,
                'route'         =>  self::$current_prefix . $route,
                'handler'       =>  $handler,
                'namespace'     =>  self::$current_namespace,
                'name'          =>  $name,
                'middlewares'   =>  [
                    'before'        =>  self::$current_middleware_before,
                    'after'         =>  self::$current_middleware_after
                ]
            ];
            
            
            self::$rules[] = [
                'httpMethod'    =>  $method,
                'route'         =>  self::$current_prefix . $route,
                'handler'       =>  $handler,
                'namespace'     =>  self::$current_namespace,
                'name'          =>  $name
            ];
        }

        /*self::$rules[] = [
            'httpMethod'    =>  self::ALL_HTTP_METHODS,
            'route'         =>  self::$current_prefix . $route,
            'handler'       =>  $handler,
            'namespace'     =>  self::$current_namespace,
            'name'          =>  $name
        ];*/

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
            if (!is_null($name)) {
                self::$route_names[$name] = self::$current_prefix . $route;
            }

            self::$rules[] = [
                'httpMethod'    =>  $method,
                'route'         =>  self::$current_prefix . $route,
                'handler'       =>  $handler,
                'namespace'     =>  self::$current_namespace,
                'name'          =>  $name,
                'middlewares'   =>  [
                    'before'        =>  self::$current_middleware_before,
                    'after'         =>  self::$current_middleware_after
                ]
            ];
        }


        if (!is_null($name)) {
            self::$route_names[$name] = self::$current_prefix . $route;
        }
    }

    /**
     * @param array $options [<br>
     *      string 'prefix' => '',<br>
     *      string 'namespace' => '',<br>
     *      callable 'before' = null,<br>
     *      callable 'after' => null<br>
     * ]
     * @param callable|null $callback
     * @param string $name
     * @return mixed|void
     */
    public static function group(array $options = [], callable $callback = null)
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

        if (array_key_exists('before', $options) && self::is_handler($options['before'])) {
            self::$stack_middlewares_before->push($options['before']);
            self::$current_middleware_before = $options['before'];

            self::$stack_middlewares_after->push($options['after']);
            self::$current_middleware_after = $options['after'];
        }

        $callback();

        if (array_key_exists('after', $options) && self::is_handler($options['after'])) {
            self::$current_middleware_after = self::$stack_middlewares_after->pop();
            self::$current_middleware_before = self::$stack_middlewares_before->pop();
        }

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
     * @param string $name
     * @return string|array
     */
    public static function getRouter($name = '')
    {
        if ($name === '*') {
            return self::$route_names;
        }
        
        if ($name === '') {
            return '/';
        }

        if (array_key_exists($name, self::$route_names)) {
            return self::$route_names[ $name ];
        }
    
        return '/';
    }

    /**
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

                // Чтобы передавать доп.параметры в правило - нужно расширять метод addRoute
                $r->addRoute($rule['httpMethod'], $rule['route'], $handler);
            }
        });

        // Fetch method and URI from somewhere
        self::$routeInfo = $routeInfo = (self::$dispatcher)->dispatch(self::$httpMethod, self::$uri);

        list($state, $handler, $method_parameters) = $routeInfo;

        //@todo: никак невозможно выяснить, какому правилу сопоставлен обработанный URL. Имеет ли смысл создать issue для пакета, который не обновлялся много лет?

        // dispatch errors
        if ($state === FastRoute\Dispatcher::NOT_FOUND) {
            // URL or URI? https://ru.wikipedia.org/wiki/URI
            throw new AppRouterNotFoundException(self::jsonize([
                'message'   =>  "URL not found",
                'method'    =>  self::$httpMethod,
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

        // нужно получить параметры правила для обработанного роута! (к сожалению, не получится получить именно для ОБРАБОТАННОГО, только для объявленного)
        // т.е., если в роуте есть опциональные части или плейсхолдеры - роут работать не будет.

        $rules = self::getRoutingRules();
        $rules_key = self::$httpMethod . ' ' . self::$uri;
        $rule = array_key_exists($rules_key, $rules) ? $rules[$rules_key] : [];

        $actor = self::compileHandler($handler);

        if (self::is_handler($rule['middlewares']['before'])){
            $before = self::compileHandler($rule['middlewares']['before']);
            $before();
            unset($before);
        }

        call_user_func_array($actor, $method_parameters);

        if (self::is_handler($rule['middlewares']['after'])){
            $after = self::compileHandler($rule['middlewares']['after']);
            $after();
            unset($after);
        }

        unset($state, $rules, $rules_key, $rule);
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
            $key = is_array($record['httpMethod']) ? "ANY {$record['route']}" : "{$record['httpMethod']} {$record['route']}";

            if (array_key_exists($key, $rules)) {
                $key .= " [ DUPLICATE ROUTE " . microtime(false) . ' ]';
            }

            $rules[ $key ] = [
                'handler'   =>  is_callable($record['handler']) ? "Closure" : $record['handler'],
                'namespace' =>  $record['namespace'],
                'name'      =>  $record['name'],
                'middlewares'   =>  [
                    'before'        =>  $record['middlewares']['before'],
                    'after'         =>  $record['middlewares']['after']
                ]
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

    /**
     * Выясняет, является ли передаваемый аргумент допустимым хэндлером
     *
     * @param $handler
     * @param bool $validate_handlers
     *
     * @return bool
     */
    public static function is_handler($handler = null, $validate_handlers = false)
    {
        if (is_null($handler)) {
            return false;
        } elseif ($handler instanceof \Closure) {
            return true;
        } elseif (strpos($handler, '@') > 0) {
            // dynamic method
            list($class, $method) = explode('@', $handler, 2);

            if ($validate_handlers && !class_exists($class)) {
                return false;
            }

            if ($validate_handlers && !method_exists($class, $method)) {
                return false;
            }

            return true;
        } elseif (strpos($handler, '::')) {
            // static method
            list($class, $method) = explode('::', $handler, 2);

            if ($validate_handlers && !class_exists($class)){
                return false;
            }

            if ($validate_handlers && !method_exists($class, $method)){
                return false;
            }

            return true;
        } else {
            // function
            if ($validate_handlers && !function_exists($handler)){
                return false;
            }

            return true;
        }
    } // is_handler()

    private static function compileHandler($handler)
    {
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

        return $actor;
    }

}

# -eof-
