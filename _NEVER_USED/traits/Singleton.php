<?php

/**
 * User: Karel Wintersky
 *
 * Trait Traits\Singleton
 * Namespace: Arris
 *
 * Library: https://github.com/KarelWintersky/Arris
 *
 * Date: 10.12.2020
 *
 */

namespace Arris\Traits;

if (!trait_exists( 'Singleton' )) {
    
    trait Singleton
    {
        /**
         * @var Singleton ссылка на инстанс
         */
        private static $instance;
        
        /**
         * gets the instance via lazy initialization (created on first usage).
         * Получаем инстанс класса через ленивую инициализацию (создается при первом использовании)
         *
         * @return self
         */
        final public static function getInstance()
        {
            return static::$instance ?? (static::$instance = new static());
        }
    
        /**
         * Singleton constructor.
         * Конструктор.
         */
        final private function __construct()
        {
            $this->init();
        }
    
        /**
         * Метод инициализации. Может быть переопределен в классе, использующем трейт.
         */
        protected function init()
        {
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
         */
        final private function __wakeup()
        {
        }
    }
}