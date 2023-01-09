<?php

namespace Arris;

use Psr\Log\LoggerInterface;

interface AppRouterInterface
{

    /**
     * Инициализирует статик-класс
     *
     * @param LoggerInterface|null $logger
     * @param array $options
     */
    public static function init(LoggerInterface $logger = null, array $options = []);

    /**
     * Устанавливает namespace по умолчанию (дублируется в опциях init() )
     *
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
     * Create routing group with options
     * Создает группу роутов
     *
     * Возможные ключи в списке опций:
     * - prefix (URL prefix)
     * - namespace
     * - before (middleware handler)
     * - after (middleware handler)
     *
     * @param array $options
     * @param callable $callback
     * @return mixed
     */
    public static function group(array $options = [], callable $callback = null);

    /**
     * Dispatch routing
     *
     * @throws \Exception
     */
    public static function dispatch();


    public static function getRouter($name = '');

    /**
     * Возвращает информацию о текущем роутинге
     *
     * @return array
     */
    public static function getRoutingInfo();

    /**
     * @return mixed
     */
    public static function getRoutersNames();

    /**
     * Возвращает список объявленных роутов: [ 'method route' => [ handler, namespace, name ]
     *
     * @return array
     */
    public static function getRoutingRules();

}