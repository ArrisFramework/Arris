<?php /** @noinspection ALL */

/**
 * User: Karel Wintersky
 *
 * Class TimerStats
 * Namespace: Arris
 *
 * Date: 10.04.2018, time: 6:14
 */

namespace Arris;

/**
 * Class TimerStats
 *
 * @package Arris
 */
class TimerStats
{
    const VERSION = "1.14.1";

    const STATE_NEW = 0;
    const STATE_RUNNING = 1;
    const STATE_PAUSED = 2;
    const STATE_STOPPED = 3;

    public static $timers = array();

    /**
     * Создает таймер с указанным идентификатором и описанием.
     * Если не указать имя - будет использован таймер по умолчанию - default.
     * Если такой таймер уже существует - он будет уничтожен (и пересоздан)
     *
     * @param string $name
     * @param string $desc
     */
    public static function init($name = '', $desc = '')
    {
        if ($name === '') $name = 'default';

        if (\array_key_exists($name, self::$timers))
            unset( self::$timers[ $name ]);

        self::$timers[ $name ] = array(
            'name'      =>  $name,
            'desc'      =>  $desc,
            'state'     =>  self::STATE_NEW,
            'starttime' =>  0,
            'totaltime' =>  0,
            'iterations'=>  0
        );
    }

    /**
     * Запускает таймер с указанным идентификатором
     *
     * @param string $name
     */
    public static function go($name = '')
    {
        if ($name === '') $name = 'default';

        if (self::$timers[ $name ]['state'] == self::STATE_STOPPED) {
            self::$timers[ $name ]['totaltime'] = 0;
            self::$timers[ $name ]['iterations'] = 0;
        }

        self::$timers[ $name ]['state'] = self::STATE_RUNNING;
        self::$timers[ $name ]['starttime'] = microtime(true);
        self::$timers[ $name ]['iterations']++;
    }

    /**
     * Ставит на паузу таймер с указанным идентификатором
     *
     * @param string $name
     */
    public static function pause($name = '')
    {
        if ($name === '') $name = 'default';

        self::$timers[ $name ]['state'] = self::STATE_PAUSED;
        self::$timers[ $name ]['totaltime'] += ( \microtime(true) - self::$timers[ $name ]['starttime']);
    }

    /**
     * Останавливает таймер с указанным идентификатором и возвращает его значение в миллисекундах.
     * При следующем запуске отсчет начнется сначала, т.е. значение таймера сбросится.
     *
     * @param string $name
     * @return mixed
     */
    public static function stop($name = '')
    {
        if ($name === '') $name = 'default';

        self::$timers[ $name ]['state'] = self::STATE_STOPPED;
        self::$timers[ $name ]['totaltime'] += ( \microtime(true) - self::$timers[ $name ]['starttime']);
        return self::$timers[ $name ]['totaltime'];
    }

    /**
     * Останавливает все таймеры
     */
    public static function stopAll()
    {
        foreach (self::$timers as $n => $timer) {
            if ($timer['iterations'] == 0) {
                unset(self::$timers[$n]);
                continue;
            }
            if ((self::$timers[ $n ]['state'] != self::STATE_STOPPED) && (self::$timers[ $n ]['state'] != self::STATE_PAUSED))
            {
                self::$timers[ $n ]['totaltime'] += ( \microtime(true) - self::$timers[ $n ]['starttime']);
                self::$timers[ $n ]['state'] = self::STATE_STOPPED;
            }
        }
    }

    /**
     * Возвращает значение указанного таймера
     *
     * @param string $name
     * @return mixed
     */
    public static function get($name = '')
    {
        if ($name === '') $name = 'default';
        return self::$timers[ $name ]['totaltime'];
    }

    /**
     * Уничтожает указанный таймер
     * @param string $name
     * @return bool
     */
    public static function destroy($name = '')
    {
        if ($name === '') $name = 'default';
        if (\array_key_exists($name, self::$timers)) {
            unset( self::$timers[ $name ]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Проверяет существование указанного таймера
     * @param string $name
     * @return bool
     */
    public static function is_exists($name = '')
    {
        if ($name === '') $name = 'default';

        return \array_key_exists($name, self::$timers);
    }

    /**
     * Возвращает статус указанного таймера
     * @param string $name
     * @return bool
     */
    public static function get_state($name = '')
    {
        if ($name === '') $name = 'default';
        if (array_key_exists($name, self::$timers)) {
            return self::$timers[ $name ]['state'];
        } else {
            return false;
        }
    }

    /**
     * Возвращает все таймеры в виде массива:
     *   идентификатор_таймера => [ name => имя таймера, desc => описание таймера, time => время работы таймера ]
     * @return array
     */
    public static function get_all_timers()
    {
        array_walk(self::$timers, function(&$item){
            unset($item['state']);
            unset($item['starttime']);
            unset($item['iterations']);
            $item['time'] = $item['totaltime'];
            unset($item['totaltime']);
        });
        return self::$timers;
    }

}

# eof
