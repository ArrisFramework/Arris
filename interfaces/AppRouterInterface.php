<?php

namespace Arris;

use Monolog\Logger;

interface AppRouterInterface
{

    /**
     * Инициализирует статик-класс
     *
     * @param Logger $logger
     */
    public static function init($logger = null, $options = []);

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
    public static function get($route, $handler, $name);

    /**
     * Helper method POST
     *
     * @param $route
     * @param $handler
     */
    public static function post($route, $handler);

    /**
     * Helper method PUT
     *
     * @param $route
     * @param $handler
     */
    public static function put($route, $handler);

    /**
     * Helper method PATCH
     *
     * @param $route
     * @param $handler
     */
    public static function patch($route, $handler);

    /**
     * Helper method DELETE
     *
     * @param $route
     * @param $handler
     */
    public static function delete($route, $handler);

    /**
     * Helper method HEAD
     *
     * @param $route
     * @param $handler
     */
    public static function head($route, $handler);

    /**
     * Add route method
     *
     * @param $httpMethod
     * @param $route
     * @param $handler
     */
    public static function addRoute($httpMethod, $route, $handler);

    /**
     * Namespace grouping (BAD NAME)
     *
     * @param $namespace
     * @param callable $callback
     */
    public static function groupNamespace($namespace, callable $callback);

    /**
     * Dispatch routing
     *
     * @throws \Exception
     */
    public static function dispatch();

}