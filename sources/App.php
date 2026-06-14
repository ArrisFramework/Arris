<?php
declare(strict_types=1);

namespace Arris;

use Arris\Core\Dot;
use RuntimeException;

class App /*implements AppInterface*/
{
    /**
     * Реестр инстансов. Ключ - имя класса (static::class).
     * Это элегантно решает проблему наследования синглтонов из твоего треда на SO.
     *
     * @var array<class-string<static>, static>
     */
    private static array $instances = [];

    private readonly Dot $repository;
    private readonly Dot $services;
    private readonly AppConfig $config;

    private ?Dot $magic_repo = null;

    /**
     * Закрытый конструктор.
     */
    final private function __construct(
        private readonly array $config_files = [],
        array $options = [],
        array $services = []
    ) {
        // Запрашиваем дефолты у наследника (App\App) и передаем их в AppConfig
        $this->config = AppConfig::getInstance($this->config_files, $this->getDefaultConfig());
        $this->repository = new Dot($options);
        $this->services = new Dot();

        foreach ($services as $name => $service) {
            $this->addService($name, $service);
        }
    }

    /**
     * Метод-заглушка. Переопределяется в классе приложения (App\App).
     */
    protected function getDefaultConfig(): array
    {
        return [];
    }

    public static function getInstance(array $config_files = [], array $options = [], array $services = []): static
    {
        $class = static::class;

        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static($config_files, $options, $services);
        } elseif (!empty($options)) {
            // Если инстанс уже создан, но переданы новые опции - merging
            self::$instances[$class]->add($options);
        }

        return self::$instances[$class];
    }

    public static function factory(array $config_files = [], array $options = [], array $services = []): static
    {
        return static::getInstance($config_files, $options, $services);
    }

    public static function key(string $key, mixed $default = null): mixed
    {
        return static::getInstance()->get($key, $default);
    }

    public static function config(?string $key = null, mixed $value = null): mixed
    {
        $instance = static::getInstance();
        return func_num_args() === 1 ? $instance->getConfig($key) : $instance->setConfig($key, $value);
    }

    /* ===================== DI & SERVICES =========================== */

    public function addService(string $name, mixed $definition = null): void
    {
        $this->services->set($name, $definition);
    }

    public function getService(string $name): mixed
    {
        return $this->services->get($name);
    }

    public function isService(string $name): bool
    {
        return $this->services->has($name);
    }

    public function getServiceType(string $name): ?string
    {
        if (!$this->isService($name)) return null;

        $instance = $this->services->get($name);
        // PHP 8.0+ match expression
        return match(true) {
            is_object($instance) => get_class($instance),
            is_resource($instance) => get_resource_type($instance),
            default => gettype($instance)
        };
    }

    /* ===================== REPOSITORY & CONFIG =========================== */

    public function add(mixed $keys, mixed $value = null): void { $this->repository->add($keys, $value); }
    public function set(string $key, mixed $data = null): void { $this->repository->set($key, $data); }
    public function get(?string $key = null, mixed $default = null): mixed { return $this->repository->get($key, $default); }

    public function getConfig(?string $key = null): mixed
    {
        return is_null($key) ? $this->config : $this->config->get($key);
    }

    public function setConfig(string $key, mixed $value = null): void { $this->config->set($key, $value); }
    /*public function addConfig(array|Dot $config): void { $this->config->add($config); }*/

    /* ===================== MAGIC & PROTECTION =========================== */
    public function __invoke(?string $key = null, mixed $data = null): mixed { return is_null($data) ? $this->get($key) : $this->set($key, $data); }
    public function __set(string $key, mixed $value): void { $this->magic_repo ??= new Dot(); $this->magic_repo->set($key, $value); }
    public function __isset(string $key): bool { return $this->magic_repo?->has($key) ?? false; }
    public function __get(string $key): mixed { return $this->__isset($key) ? $this->magic_repo->get($key) : null; }

    private function __clone() {}
    final public function __serialize(): array { throw new RuntimeException("Cannot serialize " . static::class); }
    final public function __unserialize(array $data): void { throw new RuntimeException("Cannot unserialize " . static::class); }
}