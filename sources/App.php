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
     * Репозиторий App
     *
     * @var Dot
     */
    private $repository = null;

    /**
     * "Магический" репозиторий App для методов __set, __get, __isset
     *
     * @var array|null|Dot
     */
    private $magic_repo = null;
    
    /**
     * DOT-Репозиторий конфига
     *
     * @var array|null|Dot $config
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
    
    public static function config($key = null, $value = null)
    {
        if (is_null($value)) {
            return (self::getInstance())->getConfig($key);
        }

        return (self::getInstance())->setConfig($key, $value);
    }
    
    /**
     * Возвращает инстанс синглтона App (вызывается обёрткой)
     *
     * @param null $options
     * @return App
     */
    protected static function getInstance($options = null)
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

        if (is_null($this->config)) {
            $this->config = new Dot();
        }
    }
    
    public function add($keys, $value = null)
    {
        $this->repository->add($keys, $value);
    }
    
    public function set($key, $data = null)
    {
        return $this->repository->set($key, $data);
    }
    
    public function get($key = null, $default = null)
    {
        return $this->repository->get($key, $default);
    }

    public function getConfig($key = null)
    {
        return  is_null($key)
                ? $this->config
                : $this->config[$key];
    }
    
    public function setConfig($key, $value = null)
    {
        $this->config->set($key, $value);
    }

    public function getConfigJSON($key = null)
    {
        return  is_null($key)
            ? $this->config->toJson()
            : $this->config[$key]->toJson();
    }

    /* ===================== MAGIC METHODS =========================== */

    public function __invoke($key = null, $data = null)
    {
        return
            is_null($data)
            ? $this->repository->get($key)
            : $this->repository->set($key, $data);
    }

    public function __set($key, $value)
    {
        if (is_null($this->magic_repo)) {
            $this->magic_repo = new Dot([]);
        }
        $this->magic_repo->set($key, $value);
    }

    public function __isset($key)
    {
        return $this->magic_repo->has($key);
    }

    public function __get($key)
    {
        return $this->__isset($key) ? $this->magic_repo->get($key) : null;
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

}

# -eof-
