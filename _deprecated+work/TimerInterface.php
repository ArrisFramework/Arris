<?php

namespace Arris;

interface TimerInterface
{
    /**
     * Дефолтное имя таймера
     */
    public const DEFAULT_INTERNAL_NAME = 'default';
    
    /**
     * Runtime-состояния
     */
    public const STATE_UNDEFINED = 0;
    public const STATE_NEW = 1;
    public const STATE_RUNNING = 2;
    public const STATE_PAUSED = 4;
    public const STATE_STOPPED = 8;
    
    /**
     * Создает таймер с указанным идентификатором и описанием.
     * Если не указать имя - будет использован таймер по умолчанию - default.
     * Если такой таймер уже существует - он будет уничтожен (и пересоздан)
     *
     * @param string|null $name
     * @param int $round
     */
    public static function init(string $name = null, int $round = 6);
    
    /**
     * Создает таймер и запускает с указанным идентификатором и описанием.
     * Если не указать имя - будет использован таймер по умолчанию - default.
     * Если такой таймер уже существует - он будет уничтожен (и пересоздан)
     *
     * @param string|null $name
     */
    public static function start(string $name = null);

    /**
     * Запускает таймер с указанным идентификатором
     *
     * @param string|null $name
     */
    public static function go(string $name = null);
    
    /**
     * Ставит на паузу таймер с указанным идентификатором
     *
     * @param string|null $name
     * @return string
     */
    public static function pause(string $name = null):string;
    
    /**
     * Останавливает таймер с указанным идентификатором и возвращает его значение в миллисекундах.
     * При следующем запуске отсчет начнется сначала, т.е. значение таймера сбросится.
     *
     * @param string|null $name
     * @return string
     */
    public static function stop(string $name = null):string;

    /**
     * Останавливает все таймеры
     */
    public static function stopAll();

    /**
     * Возвращает значение указанного таймера
     *
     * @param string $name
     * @return mixed
     */
    public static function get(string $name = null);

    /**
     * Возвращает все таймеры в виде массива:
     *   идентификатор_таймера => [ name => имя таймера, desc => описание таймера, time => время работы таймера ]
     * @return array
     */
    public static function get_all_timers();

    /**
     * Возвращает статус указанного таймера
     * @param string $name
     * @return bool
     */
    public static function get_state(string $name = null);

    /**
     * Уничтожает указанный таймер
     * @param string $name
     * @return bool
     */
    public static function destroy($name = null);

    /**
     * Проверяет существование указанного таймера
     * @param string $name
     * @return bool
     */
    public static function is_exists($name = null);

}