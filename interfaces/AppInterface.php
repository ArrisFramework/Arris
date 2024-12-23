<?php

namespace Arris;

use Arris\Core\Dot;

interface AppInterface
{
    /**
     * Публичный метод, возвращает инстанс App (или его наследников)
     *
     * @param array $config
     * @param array $options
     * @param array $services
     * @return App
     */
    public static function factory($config = [], $options = [], $services = []): ?App;
    
    /**
     * Инстанциирует App и возвращает значение по ключу.
     * Краткая форма, не требует предварительного вызова App::factory()
     *
     * @param $key
     * @param $default
     * @return mixed
     */
    public static function key($key, $default);
    
    /**
     * Добавляет значение в репозиторий по ключу
     *
     * @param $keys
     * @param null $value
     */
    public function add($keys, $value = null);
    
    /**
     * Устанавливает значение/значения по ключу
     *
     * @param $key
     * @param $data
     */
    public function set($key, $data = null);
    
    /**
     * Получает значение из репозитория по ключу
     *
     * @param null $key
     * @param null $default
     * @return array|mixed|null
     */
    public function get($key = null, $default = null);

    /**
     * Устанавливает конфиг для приложения (array или Dot)
     *
     * @param $key
     * @param null $value
     * @return Dot
     */
    public function setConfig($key, $value = null):Dot;

    /**
     * add config to App instance
     *
     * @param $config
     * @return Dot
     */
    public function addConfig($config): Dot;

    /**
     * Возвращает весь конфиг или ключ
     *
     * @param null $key
     * @return mixed
     */
    public function getConfig($key = null);

    /**
     * Инстанциирует App и возвращает (или устанавливает) значение в конфиге по ключу
     *
     * @param $key
     * @param null $value
     * @return mixed
     */
    public static function config($key = null, $value = null);

    /**
     * Возвращает конфиг или часть (по ключу) в виде JSON-структуры
     *
     * @param $key
     * @return string
     */
    public function getConfigJSON($key = null);

    /* MAGIC METHODS */

    /**
     * Invoke экземпляра App
     *
     * Если передано два аргумента - рассматривается как SET,
     * Если передан один аргумент - рассматривается как GET
     *
     * Позволяет использовать механизм обращения к переменной $APP с аргументами:
     * $app = (App::factory())
     * $app($key, $data) or $app($key)
     *
     * @param null $key
     * @param null $data
     * @return array|mixed|void|null
     */
    public function __invoke($key = null, $data = null);

    /**
     * Setter, хранит в "магическом" репозитории значения
     * $app->xxx = 1
     *
     * @param $key
     * @param $value
     */
    public function __set($key, $value);

    /**
     * isSet, проверяет наличие значения в "магическом" репозитории
     *
     * @param $key
     * @return bool
     */
    public function __isset($key);

    /**
     * Getter, возвращает значение из магического репозитория (или null)
     *
     * @param $key
     * @return mixed|null
     */
    public function __get($key);

    /**
     * Добавляет сервис в репозиторий сервисов.
     *
     * @param $name
     * @param $definition
     * @return void
     */
    public function addService($name, $definition = null);

    /**
     * Возвращает сервис из репозитория сервисов
     *
     * @param $name
     * @return array|mixed
     */
    public function getService($name);

    /**
     * Проверяет, существует ли сервис с переданным именем?
     *
     * @param $name
     * @return bool
     */
    public function isService($name);

}

# -eof-
