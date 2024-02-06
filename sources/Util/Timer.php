<?php

namespace Arris\Util;

/**
 * @method static   bool    go(string $name = 'default')
 * @method static   bool    start(string $name = 'default', int $precision = 6)
 * @method static   string  pause(string $name = 'default')
 * @method static   string  stop(string $name = 'default') @return string
 * @method static   bool    destroy(string $name = 'default')
 * @method static   bool    is_exists(string $name = 'default')
 * @method static   array   get_state(string $name = 'default')
 * @method static   array   get_all_timers()
 * @method static   array   stopAll()
 *
 * @method          bool    go()
 * @method          bool    start(int $precision = 6)
 * @method          string  pause()
 * @method          string  stop()
 * @method          bool    destroy()
 * @method          array   get_state()
 *
 */
class Timer
{
    /**
     * Дефолтное имя таймера
     */
    public const DEFAULT_INTERNAL_NAME = 'default';

    public const DEFAULT_PRECISION = 6;

    /**
     * Runtime-состояния
     */
    public const STATE_UNDEFINED = 0;
    public const STATE_NEW = 1;
    public const STATE_RUNNING = 2;
    public const STATE_PAUSED = 4;
    public const STATE_STOPPED = 8;

    public static $timers = [
        'default'   =>  [
            'name'          =>  self::DEFAULT_INTERNAL_NAME,
            'state'         =>  self::STATE_NEW,
            'time.start'    =>  0,
            'time.total'    =>  0,
            'iterations'    =>  0,
            'round'         =>  6
        ]
    ];

    private array $timer = [];

    public function __construct($round = 6)
    {
        $this->timer = [
            'name'          =>  self::DEFAULT_INTERNAL_NAME,
            'state'         =>  self::STATE_NEW,
            'time.start'    =>  0,
            'time.total'    =>  0,
            'iterations'    =>  0,
            'round'         =>  (int)$round
        ];
    }

    public static function init($name = null, $round = 6)
    {
        $name = self::getTimerInternalName($name);

        if (array_key_exists($name, self::$timers)) {
            unset( self::$timers[ $name ]);
        }

        self::$timers[ $name ] = [
            'name'          =>  $name,
            'state'         =>  self::STATE_NEW,
            'time.start'    =>  0,
            'time.total'    =>  0,
            'iterations'    =>  0,
            'round'         =>  (int)$round
        ];
    }

    public function __call($method, $param = [])
    {
        $timer = &$this->timer;

        $result = null;

        switch ($method) {
            case 'start': {
                $this->go();
                break;
            }
            case 'go': {
                if ($timer['state'] === self::STATE_STOPPED) {
                    $timer['time.total'] = 0;
                    $timer['iterations'] = 0;
                }

                $timer['state'] = self::STATE_RUNNING;
                $timer['time.start'] = microtime(true);
                $timer['iterations']++;
                $result = true;

                break;
            }
            case 'pause': {
                $timer['state'] = self::STATE_PAUSED;
                $timer['time.total'] += ( microtime(true) - $timer['time.start']);

                $result = number_format($timer['time.total'], $timer['round'], '.', '');
                break;
            }
            case 'stop': {
                $timer['state'] = self::STATE_STOPPED;
                $timer['time.total'] += ( microtime(true) - $timer['time.start']);
                $result = number_format($timer['time.total'], $timer['round'], '.', '');
                break;
            }
            case 'get': {
                $result = $timer['time.total'];
                break;
            }
            case 'destroy': {
                $timer = [];
                $result = true;
                break;
            }
            case 'is_exists': {
                $result = true;
                break;
            }
            case 'get_state': {
                $result = $timer['state'];
                break;
            }
        }
        return $result;
    }

    public static function __callStatic($method, $param = [])
    {
        $result = null;

        $name = empty($param) ? self::DEFAULT_INTERNAL_NAME : $param[0];
        $name = self::getTimerInternalName($name);

        switch ($method) {
            case 'start': {
                $round = $param[1] ?? self::DEFAULT_PRECISION;
                self::init($name, $round);
                self::go($name);
                break;
            }
            case 'go': {
                $timer = &self::$timers[ $name ];

                if ($timer['state'] === self::STATE_STOPPED) {
                    $timer['time.total'] = 0;
                    $timer['iterations'] = 0;
                }

                $timer['state'] = self::STATE_RUNNING;
                $timer['time.start'] = microtime(true);
                $timer['iterations']++;

                $result = $timer['time.start'];

                break;
            }
            case 'pause': {
                $timer = &self::$timers[ $name ];

                $timer['state'] = self::STATE_PAUSED;
                $timer['time.total'] += ( microtime(true) - $timer['time.start']);

                $result = number_format($timer['time.total'], $timer['round'], '.', '');
                break;
            }
            case 'stop': {
                $timer = &self::$timers[ $name ];

                $timer['state'] = self::STATE_STOPPED;
                $timer['time.total'] += ( microtime(true) - $timer['time.start']);
                $result = number_format($timer['time.total'], $timer['round'], '.', '');
                break;
            }
            case 'stopAll': {
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
                break;
            }
            case 'get': {
                $result = self::$timers[ $name ]['time.total'];
                break;
            }
            case 'destroy': {
                if (array_key_exists($name, self::$timers)) {
                    unset( self::$timers[ $name ]);
                    $result =  true;
                } else {
                    $result =  false;
                }

                break;
            }
            case 'is_exists': {
                $result = array_key_exists($name, self::$timers);
                break;
            }
            case 'get_state': {
                if (array_key_exists($name, self::$timers)) {
                    $result = self::$timers[ $name ]['state'];
                } else {
                    $result =  self::STATE_UNDEFINED;
                }

                break;
            }
            case 'get_all_timers': {
                array_walk(self::$timers, function(&$item){
                    unset($item['state']);
                    unset($item['time.start']);
                    unset($item['iterations']);
                    $item['time'] = $item['time.total'];
                    unset($item['time.total']);
                });
                $result = self::$timers;
                break;
            }
            default: {
                $result = false;
            }
        }
        return $result;
    }

    private static function getTimerInternalName($name = null)
    {
        return
            (is_null($name) or $name === '')
                ? self::DEFAULT_INTERNAL_NAME
                : $name;
    }


}