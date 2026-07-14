<?php
declare(strict_types=1);

namespace Arris;

use Arris\Core\Dot;
use RuntimeException;

class App implements AppInterface
{
    /**
     * Реестр инстансов. Ключ - имя класса (static::class).
     * Это элегантно решает проблему наследования синглтонов из твоего треда на SO.
     *
     * @var array<class-string<static>, static>
     */
    private static array $instances = [];

    /**
     * Репозиторий опций класса
     * @var Dot
     */
    private readonly Dot $options;

    /**
     * @var Dot
     */
    private readonly Dot $services;

    /**
     * @var AppConfig
     */
    private readonly AppConfig $config;

    /**
     * Закрытый конструктор.
     */
    final private function __construct(
        private readonly array $config_files = [],
        array $options = [],
        array $services = []
    ) {
        // Запрашиваем дефолты у наследника (App\App) и передаем их в AppConfig
        $this->config = new AppConfig($this->config_files, $this->getDefaultConfig());

        $this->options = new Dot($options);
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

    /**
     * @param array $config_files - пути к конфигурационным файлам (? перед файлом - опциональный)
     * @param array $options - кастомные опции
     * @param array $services - кастомные сервисы
     *
     * @return static
     */
    public static function getInstance(array $config_files = [], array $options = [], array $services = []): static
    {
        $class = static::class;

        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static($config_files, $options, $services);
        } elseif (!empty($options)) {
            // Если инстанс уже создан, но переданы новые опции - мержим
            self::$instances[$class]->add($options);
        }

        return self::$instances[$class];
    }

    /**
     * Алиас getInstance()
     *
     * @param array $config_files
     * @param array $options
     * @param array $services
     *
     * @return static
     */
    public static function factory(array $config_files = [], array $options = [], array $services = []): static
    {
        return static::getInstance($config_files, $options, $services);
    }

    /**
     * Возвращает значение из репозитория
     *
     * @param string $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public static function key(string $key, mixed $default = null): mixed
    {
        return static::getInstance()->get($key, $default);
    }

    /**
     * Статический метод-хелпер, аналог функции "config()"
     *
     * Передано аргументов:
     * 0: возвращаем ВЕСЬ конфиг
     * 1: возвращаем значение аргумента
     * 2: устанавливаем ключу значение
     *
     * Может быть вызвано в виде config(key, default: value) -
     * В этом случае возвращает значение ключа ИЛИ дефолтное значение.
     * (В этом случае передается как бы 3 аргумента)
     *
     * Поддерживается DOT-нотация.
     *
     * @param string|null $key
     * @param mixed|null $value
     * @param mixed|null $default
     *
     * @return mixed
     */
    public static function config(?string $key = null, mixed $value = null, mixed $default = null): mixed
    {
        $instance = static::getInstance();

        return match (func_num_args()) {
            0 => $instance->getConfig(null),
            1 => $instance->getConfig($key),
            2 => $instance->setConfig($key, $value),
            3 => $instance->getConfig($key, $default)
        };
    }

    /**
     * Статический метод.
     * Возвращает значение из конфига или $default, если ключ не найден.
     *
     * @param string $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public static function fromConfig(string $key, mixed $default = null):mixed
    {
        return static::getInstance()->getConfig($key, $default);
    }

    /**
     * Статический метод.
     * Устанавливает значение в конфиге
     *
     * @param string $key
     * @param mixed|null $value
     *
     * @return void
     */
    public static function toConfig(string $key, mixed $value = null):void
    {
        static::getInstance()->setConfig($key, $value);
    }

    /**
     * Возвращает ВЕСЬ конфиг как объект AppConfig
     * Используется, к примеру, для проброса в шаблоны, внутри которых ключи доступны с дот-нотацией.
     *
     * @return AppConfig
     */
    public static function theConfig(): AppConfig
    {
        return static::getInstance()->config;
    }

    /**
     * Получить конфиг для инстанса App
     *
     * @param string|null $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getConfig(?string $key = null, mixed $default = null): mixed
    {
        return is_null($key) ? $this->config : $this->config->get($key, $default);
    }

    /**
     * Установить параметр в конфиге инстанса App.
     *
     * @param string $key
     * @param mixed|null $value
     *
     * @return App
     */
    public function setConfig(string $key, mixed $value = null): static
    {
        $this->config->set($key, $value);
        return $this;
    }

    /**
     * Проверяет существование ключа в конфиге инстанса App
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasConfig(string $key): bool
    {
        return $this->config->has($key);
    }

    /**
     * Удаляет ключ из конфига.
     */
    public function removeConfig(string $key): void
    {
        $this->config->remove($key);
    }

    /**
     * Возвращает весь конфиг как массив.
     *
     * @return array
     */
    public function allConfig(): array
    {
        return $this->config->all();
    }

    /**
     * Заменяет конфиг целиком.
     *
     * @param array $config
     *
     * @return void
     */
    public function replaceConfig(array $config): void
    {
        $this->config->replace($config);
    }

    /**
     * Массовое добавление/слияние данных в конфигурацию.
     *
     * @param array|Dot $config
     *
     * @return $this
     */
    public function addConfig(array|Dot $config): static
    {
        $this->config->add($config instanceof Dot ? $config->all() : $config);
        return $this;
    }


    /* ===================== DI & SERVICES =========================== */

    /**
     * Добавляет сервис в репозиторий сервисов
     *
     * @param string $name      Уникальное имя сервиса
     * @param mixed $definition Объект, Closure или массив конфигурации
     *
     * @return void
     */
    public function addService(string $name, mixed $definition = null): void
    {
        $this->services->set($name, $definition);
    }

    /**
     * Возвращает инстанс сервиса из репозитория сервисов
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getService(string $name): mixed
    {
        return $this->services->get($name);
    }

    /**
     * Проверяет, зарегистрирован ли сервис (в репозитории сервисов)
     *
     * @param string $name
     *
     * @return bool
     */
    public function isService(string $name): bool
    {
        return $this->services->has($name);
    }

    /**
     * Возвращает тип зарегистрированного сервиса (class name, resource type или примитив)
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getServiceType(string $name): ?string
    {
        if (!$this->isService($name)) return null;

        $instance = $this->services->get($name);
        return match(true) {
            is_object($instance) => get_class($instance),
            is_resource($instance) => get_resource_type($instance),
            default => gettype($instance)
        };
    }

    /* ===================== ОПЦИИ (Репозиторий опций App) =========================== */

    /**
     * Массовое добавление данных в репозиторий опций.
     *
     * @param mixed $keys Массив данных или строковый ключ
     * @param mixed $value Значение (если $keys - строка)
     * @return $this
     */
    public function add(mixed $keys, mixed $value = null): static { $this->options->add($keys, $value); return $this; }

    /**
     * Устанавливает значение в репозиторий опций.
     *
     * @param string $key Ключ
     * @param mixed $data Значение
     * @return $this
     */
    public function set(string $key, mixed $data = null): static { $this->options->set($key, $data); return $this; }

    /**
     * Получает значение из репозитория опций
     *
     * @param string|null $key Ключ (или null для получения всего репозитория)
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get(?string $key = null, mixed $default = null): mixed { return $this->options->get($key, $default); }

    /**
     * Проверяет существование ключа в репозитории опций.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool { return $this->options->has($key); }

    /**
     * Удаляет ключ из репозитория опций.
     *
     * @param string $key
     *
     * @return void
     */
    public function remove(string $key): void { $this->options->delete($key); }

    /**
     * Возвращает все опции как массив.
     *
     * @return array
     */
    public function all(): array { return $this->options->all(); }


    /* ===================== MAGIC & PROTECTION =========================== */

    /**
     * Магический invoke - чтение или запись в репозиторий опций
     *
     * @param string|null $key
     * @param mixed|null $data
     *
     * @return mixed
     */
    public function __invoke(?string $key = null, mixed $data = null): mixed
    {
        return is_null($data) ? $this->get($key) : $this->set($key, $data);
    }

    /**
     * Магическая установка значения в репозиторий опций
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->options->set($key, $value);
    }

    /**
     * Проверка существования значения в репозитории опций
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset(string $key): bool { return $this->options->has($key); }

    /**
     * Получение данных из репозитория опций
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get(string $key): mixed { return $this->options->get($key); }

    /* =========================== ЗАПРЕТЫ ДЕЙСТВИЙ С КЛАССОМ ================================= */

    /**
     * Запрет клонирования
     * @return mixed
     */
    final public function __clone() { throw new RuntimeException("Cannot clone " . static::class); }

    /**
     * Запрет сериализации
     * @return array
     */
    final public function __serialize(): array { throw new RuntimeException("Cannot serialize " . static::class); }

    /**
     * Запрет десериализации
     * @param array $data
     *
     * @return void
     */
    final public function __unserialize(array $data): void { throw new RuntimeException("Cannot unserialize " . static::class); }

    /**
     * Сброс реестра инстансов.
     * Используется ТОЛЬКО в Unit-тестах для изоляции тестов друг от друга.
     * В production-коде этот метод вызывать НЕ НУЖНО.
     */
    public static function reset(): void
    {
        self::$instances = [];
    }


}