<?php


namespace Arris;

interface TimerStatsInterface {

    /**
     * Создает таймер с указанным идентификатором и описанием.
     * Если не указать имя - будет использован таймер по умолчанию - default.
     * Если такой таймер уже существует - он будет уничтожен (и пересоздан)
     *
     * @param string $name
     * @param string $desc
     */
    public static function init($name = null, $desc = null);

    /**
     * Запускает таймер с указанным идентификатором
     *
     * @param string $name
     */
    public static function go($name = null);

    /**
     * Ставит на паузу таймер с указанным идентификатором
     *
     * @param string $name
     */
    public static function pause($name = null);

    /**
     * Останавливает таймер с указанным идентификатором и возвращает его значение в миллисекундах.
     * При следующем запуске отсчет начнется сначала, т.е. значение таймера сбросится.
     *
     * @param string $name
     * @return mixed
     */
    public static function stop($name = null);

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
    public static function get($name = null);

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
    public static function get_state($name = null);

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