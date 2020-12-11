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

final class App implements AppInterface
{
    /**
     * @var App ссылка на инстанс
     */
    private static $instance;
    
    /**
     * @var Dot
     */
    private $repo = null;
    
    final public static function factory($options = null)
    {
        return self::getInstance($options);
    }
    
    final public static function access($options = null)
    {
        return self::getInstance($options);
    }
    
    final public static function handle($options = null)
    {
        return self::getInstance($options);
    }
    
    /**
     * Возвращает инстанс синглтона App (вызывается обёрткой)
     *
     * @param null $options
     * @return App
     */
    final private static function getInstance($options = null)
    {
        if (!self::$instance) {
            self::$instance = new self($options);
        } else {
            (self::$instance)->add($options);
        }
    
        return self::$instance;
    }
    
    final private function __construct($options = null)
    {
        if (is_null($this->repo)) {
            $this->repo = new Dot($options);
        } else if (!empty($options)) {
            $this->repo->add($options);
        }
    }
    
    public function add($keys, $value = null)
    {
        $this->repo->add($keys, $value);
    }
    
    public function set($key, $data = null)
    {
        $this->repo->set($key, $data);
    }
    
    public function get($key = null, $default = null)
    {
        return $this->repo->get($key, $default);
    }
    
    /**
     * Prevent the instance from being cloned.
     * Предотвращаем клонирование инстанса
     *
     * @return void
     */
    final private function __clone()
    {
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
        throw new Exception("Cannot unserialize an App");
    }
    
    public function __invoke($key = null, $data = null)
    {
        return
            is_null($data)
            ? $this->repo->get($key)
            : $this->repo->set($key, $data);
    }
    
}

# -eof-
