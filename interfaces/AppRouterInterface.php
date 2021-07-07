<?php

namespace Arris;

use Monolog\Logger;
use Psr\Log\LoggerInterface;

interface AppRouterInterface
{

    /**
     * Инициализирует статик-класс
     *
     * @param Logger $logger
     */
    public static function init(LoggerInterface $logger = null, $options = []);

    /**
     * Устанавливает namespace по умолчанию.
     * @todo: перенести в опции
     * @param string $namespace
     */
    public static function setDefaultNamespace(string $namespace = '');


    /**
     * Helper method GET
     *
     * @param $route
     * @param $handler
     * @param $name - route internal name
     */
    public static function get($route, $handler, $name = null);
    
    /**
     * Helper method POST
     *
     * @param $route
     * @param $handler
     * @param null $name
     */
    public static function post($route, $handler, $name = null);
    
    /**
     * Helper method PUT
     *
     * @param $route
     * @param $handler
     * @param null $name
     */
    public static function put($route, $handler, $name = null);
    
    /**
     * Helper method PATCH
     *
     * @param $route
     * @param $handler
     * @param null $name
     */
    public static function patch($route, $handler, $name = null);
    
    /**
     * Helper method DELETE
     *
     * @param $route
     * @param $handler
     * @param null $name
     */
    public static function delete($route, $handler, $name = null);
    
    /**
     * Helper method HEAD
     *
     * @param $route
     * @param $handler
     * @param null $name
     */
    public static function head($route, $handler, $name = null);
    
    /**
     * Add route method
     *
     * @param $httpMethod
     * @param $route
     * @param $handler
     * @param null $name
     */
    public static function addRoute($httpMethod, $route, $handler, $name = null);

    /**
     * Namespace grouping
     *
     * @param $namespace
     * @param callable $callback
     */
    public static function groupNamespace($namespace, callable $callback);
    
    /**
     * @param $options
     * @param callable $callback
     * @return mixed
     */
    public static function group(array $options, callable $callback);

    /**
     * Dispatch routing
     *
     * @throws \Exception
     */
    public static function dispatch();

}