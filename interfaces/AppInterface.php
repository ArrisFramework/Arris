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
}

# -eof-
