<?php

/**
 * User: Karel Wintersky
 *
 * Class App
 * Namespace: Arris
 *
 * Library: https://github.com/KarelWintersky/Arris
 *
 * Date: 10.12.2020
 *
 */

namespace Arris;

use Arris\Core\Dot;
use Exception;
use RuntimeException;

class App implements AppInterface
{
    /**
     * @var App ссылка на инстанс
     */
    private static $instance;
    
    /**
     * @var Dot
     */
    private $repository = null;
    
    /**
     * @var
     */
    private $config = null;
    
    public static function factory($options = null)
    {
        return self::getInstance($options);
    }
    
    public static function access($options = null)
    {
        return self::getInstance($options);
    }
    
    public static function handle($options = null)
    {
        return self::getInstance($options);
    }
    
    public static function key($key, $default)
    {
        return (self::getInstance())->get($key, $default);
    }
    
    /**
     * @todo: isCorrect? isUsable?
     *
     * @param $key
     * @return mixed
     */
    public static function config($key)
    {
        return (self::getInstance())->getConfig($key);
    }
    
    /**
     * Возвращает инстанс синглтона App (вызывается обёрткой)
     *
     * @param null $options
     * @return App
     */
    private static function getInstance($options = null)
    {
        if (!self::$instance) {
            self::$instance = new self($options);
        } else {
            (self::$instance)->add($options);
        }
    
        return self::$instance;
    }
    
    private function __construct($options = null)
    {
        if (is_null($this->repository)) {
            $this->repository = new Dot($options);
        } else if (!empty($options)) {
            $this->repository->add($options);
        }
    }
    
    function add($keys, $value = null)
    {
        $this->repository->add($keys, $value);
    }
    
    public function set($key, $data = null)
    {
        $this->repository->set($key, $data);
    }
    
    public function get($key = null, $default = null)
    {
        return $this->repository->get($key, $default);
    }

    // @todo: isCorrect? isUsable?
    public function getConfig($key = null)
    {
        return $this->config[$key];
    }
    
    // @todo: isCorrect? isUsable?
    public function setConfig($config)
    {
        $this->config = $config;
    }
    
    /**
     * Prevent the instance from being cloned.
     * Предотвращаем клонирование инстанса
     *
     * @return void
     */
    final private function __clone()
    {
        throw new RuntimeException("Cannot serialize an App");
    }
    
    /**
     * Prevent from being unserialized.
     * Предотвращаем десериализацию инстанса
     *
     * @return void
     * @throws Exception
     */
    final private function __wakeup()
    {
        throw new RuntimeException("Cannot unserialize an App");
    }
    
    public function __invoke($key = null, $data = null)
    {
        return
            is_null($data)
            ? $this->repository->get($key)
            : $this->repository->set($key, $data);
    }
    
}

# -eof-
