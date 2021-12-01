<?php

/**
 * User: Karel Wintersky
 *
 * Class TimerStats
 * Namespace: Arris
 *
 * Date: 10.04.2018, time: 6:14
 */

namespace Arris\Utils;

use function microtime;
use function array_key_exists;
use function number_format;
use function is_null;

/**
 * Class Timer
 *
 * @package Arris
 */
class Timer implements TimerInterface
{
    public static $timers = array();

    public static function init($name = null, $round = 6)
    {
        $name = self::getTimerInternalName($name);

        if (array_key_exists($name, self::$timers)) {
            unset( self::$timers[ $name ]);
        }

        self::$timers[ $name ] = array(
            'name'          =>  $name,
            'state'         =>  self::STATE_NEW,
            'time.start'    =>  0,
            'time.total'    =>  0,
            'iterations'    =>  0,
            'round'         =>  (int)$round
        );
    }
    
    /**
     *
     * @param null $name
     * @param int $round
     */
    public static function start($name = null, $round = 6)
    {
        self::init($name, $round);
        self::go($name);
    }

    public static function go(string $name = null)
    {
        $name = self::getTimerInternalName($name);
        $timer = &self::$timers[ $name ];

        if ($timer['state'] === self::STATE_STOPPED) {
            $timer['time.total'] = 0;
            $timer['iterations'] = 0;
        }

        $timer['state'] = self::STATE_RUNNING;
        $timer['time.start'] = microtime(true);
        $timer['iterations']++;
    }

    public static function pause($name = null):string
    {
        $name = self::getTimerInternalName($name);
        $timer = &self::$timers[ $name ];

        $timer['state'] = self::STATE_PAUSED;
        $timer['time.total'] += ( microtime(true) - $timer['time.start']);
        
        return number_format($timer['time.total'], $timer['round'], '.', '');
    }

    public static function stop($name = null):string
    {
        $name = self::getTimerInternalName($name);
        $timer = &self::$timers[ $name ];

        $timer['state'] = self::STATE_STOPPED;
        $timer['time.total'] += ( microtime(true) - $timer['time.start']);
        return number_format($timer['time.total'], $timer['round'], '.', '');
    }

    public static function stopAll()
    {
        foreach (self::$timers as $n => $timer) {
            if ($timer['iterations'] === 0) {
                unset(self::$timers[$n]);
                continue;
            }
            if ((self::$timers[ $n ]['state'] !== self::STATE_STOPPED) && (self::$timers[ $n ]['state'] !== self::STATE_PAUSED))
            {
                self::$timers[ $n ]['time.total'] += ( microtime(true) - self::$timers[ $n ]['time.start']);
                self::$timers[ $n ]['state'] = self::STATE_STOPPED;
            }
        }
    }

    public static function get($name = null)
    {
        $name = self::getTimerInternalName($name);

        return self::$timers[ $name ]['time.total'];
    }

    public static function destroy($name = null)
    {
        $name = self::getTimerInternalName($name);

        if (array_key_exists($name, self::$timers)) {
            unset( self::$timers[ $name ]);
            return true;
        }
    
        return false;
    }

    public static function is_exists($name = null)
    {
        $name = self::getTimerInternalName($name);

        return array_key_exists($name, self::$timers);
    }

    public static function get_state($name = null)
    {
        $name = self::getTimerInternalName($name);

        if (array_key_exists($name, self::$timers)) {
            return self::$timers[ $name ]['state'];
        }
    
        return self::STATE_UNDEFINED;
    }

    public static function get_all_timers()
    {
        array_walk(self::$timers, function(&$item){
            unset($item['state']);
            unset($item['time.start']);
            unset($item['iterations']);
            $item['time'] = $item['time.total'];
            unset($item['time.total']);
        });
        return self::$timers;
    }

    private static function getTimerInternalName($name = null)
    {
        return
            (is_null($name) or $name === '')
            ? self::DEFAULT_INTERNAL_NAME
            : $name;
    }

}

# eof
