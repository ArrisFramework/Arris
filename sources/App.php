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
use RuntimeException;

class App implements AppInterface
{
    /**
     * @var App|null ссылка на инстанс
     */
    private static ?App $instance = null;
    
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
    
    public static function factory($config = [], $options = [], $services = []): ?App
    {
        return self::getInstance($config, $options, $services);
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
     * @param null $config
     * @return App
     */
    protected static function getInstance($config = [], $options = [], $services = []): ?App
    {
        if (!self::$instance) {
            // not self!!! later static binding, allowing inheritance of Arris\App class
            self::$instance = new static($config, $options, $services);
        } else {
            (self::$instance)->add($options);
        }
    
        return self::$instance;
    }

    /**
     * Приватный конструктор
     *
     * @param $options
     * @param $config
     * @param $services
     */
    private function __construct($config = [], $options = [], $services = [])
    {
        if (is_null($this->config)) {
            $this->config = new Dot($config);
        }

        if (is_null($this->repository)) {
            $this->repository = new Dot();
        } else if (!empty($options)) {
            $this->repository->add($options);
        }

        if (is_null($this->services)) {
            $this->services = new Dot();
        }

        if (!empty($services)) {
            foreach ($services as $service_name => $service) {
                $this->addService($service_name, $service);
            }
        }
    }

    public function addService($name, $definition = null)
    {
        $this->services->add($name, $definition);
    }

    public function getService($name)
    {
        return $this->services->get($name);
    }

    public function isService($name)
    {
        return $this->services->has($name);
    }

    //@todo: проверить
    public function getServiceType($name)
    {
        if ($this->isService($name)) {
            $instance = $this->services->get($name);

            if (is_object($instance)) {
                return get_class($instance);
            } elseif (is_resource($instance)) {
                return get_resource_type($instance);
            } else {
                return gettype($instance);
            }
        }
        return null;
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
    
    public function setConfig($key, $value = null):Dot
    {
        if (is_array($key) || $key instanceof Dot) {
            return $this->config->replace($key);
        }

        return $this->config->set($key, $value);
    }

    /**
     * add config to App instance
     *
     * @param $config
     * @return Dot
     */
    public function addConfig($config): Dot
    {
        return $this->config->add($config);
    }

    public function getConfigJSON($key = null)
    {
        return is_null($key)
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
