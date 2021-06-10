<?php

namespace Arris;

interface AppInterface
{
    /**
     * Возвращает инстанс синглтона App (wrapper)
     *
     * @param null $options
     * @return App
     */
    public static function factory($options = null);
    
    /**
     * Alias of factory
     */
    public static function access($options = null);
    
    /**
     * Alias of factory
     */
    public static function handle($options = null);
    
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
     * Invoke экземпляра App.
     * Если передано два аргумента - рассматривается как SET,
     * Если передан один аргумент - рассматривается как GET
     *
     * @param null $key
     * @param null $data
     * @return array|mixed|void|null
     */
    public function __invoke($key = null, $data = null);
    
    /**
     * Устанавливает конфиг для приложения (array или Dot)
     *
     * @param $config
     * @return mixed
     */
    public function setConfig($config);
    
    /**
     * Возвращает весь конфиг или ключ
     *
     * @param null $key
     * @return mixed
     */
    public function getConfig($key = null);
}

# -eof-
