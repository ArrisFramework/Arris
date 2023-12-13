<?php

/**
 * Class Arris\App
 * Application container
 *
 * User: Karel Wintersky
 *
 * Library: https://github.com/KarelWintersky/Arris
 *
 * Date: 10.12.2020
 * Date: 13.12.2023
 *
 * See: https://www.php.net/manual/ru/language.oop5.late-static-bindings.php
 * https://stackoverflow.com/questions/3126130/extending-singletons-in-php
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
    private static $instance = null;
    
    /**
     * Общий репозиторий App
     *
     * @var array|null|Dot
     */
    private $repository = null;

    /**
     * "Магический" репозиторий App для методов __set, __get, __isset
     *
     * @var array|null|Dot
     */
    private $magic_repo = null;

    /**
     * Репозиторий сервисов
     *
     * @var array|null|Dot
     */
    private $services = null;
    
    /**
     * Репозиторий конфига
     *
     * @var array|null|Dot $config
     */
    private $config = null;
    
    public static function factory($options = null)
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
     * Защищенный метод, вызывается обёрткой factory, возвращает инстанс класса App,
     * при отсутствии - создает новый инстанс и возвращает.
     *
     * @param null $options
     * @return App
     */
    protected static function getInstance($options = null)
    {
        if (!self::$instance) {
            self::$instance = new static($options); // not self!!! later static binding, allowing inheritance of Arris\App class
        } else {
            (self::$instance)->add($options);
        }
    
        return self::$instance;
    }

    /**
     * Приватный конструктор
     *
     * @param $options
     */
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

        if (is_null($this->services)) {
            $this->services = new Dot();
        }
    }

    public function addService($name, $definition = null)
    {
        $this->services->add($name, $definition);
    }

    public function getService($name)
    {
        return $this->services->get($name, null);
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
     * @throws RuntimeException
     */
    final public function __clone()
    {
        throw new RuntimeException("Can't clone " . __CLASS__);
    }

    /**
     * Prevent from being unserialized.
     * Предотвращаем десериализацию инстанса
     *
     * @return void
     * @throws RuntimeException
     */
    final public function __wakeup()
    {
        throw new RuntimeException("Can't unserialize " . __CLASS__);
    }

    /**
     * Prevent from being serialized.
     * Предотвращает сериализацию инстанса.
     *
     * @return void
     * @throws RuntimeException
     */
    final public function __sleep()
    {
        throw new RuntimeException("Can't serialize " . __CLASS__);
    }

}

# -eof-
