<?php

declare(strict_types=1);

namespace Arris\Util;

class Timer
{
    public const DEFAULT_INTERNAL_NAME = 'default';
    public const DEFAULT_PRECISION = 6;

    public const STATE_UNDEFINED = 0;
    public const STATE_NEW = 1;
    public const STATE_RUNNING = 2;
    public const STATE_PAUSED = 4;
    public const STATE_STOPPED = 8;

    public static array $timers = [];

    public array $timer = [];

    public function __construct(int $round = self::DEFAULT_PRECISION)
    {
        $this->timer = [
            'name'          => self::DEFAULT_INTERNAL_NAME,
            'state'         => self::STATE_NEW,
            'time.start'    => 0.0,
            'time.total'    => 0.0,
            'iterations'    => 0,
            'round'         => $round,
        ];
    }

    public static function init(?string $name = null, int $round = self::DEFAULT_PRECISION): void
    {
        $name = self::resolveName($name);

        if (array_key_exists($name, self::$timers)) {
            unset(self::$timers[$name]);
        }

        self::$timers[$name] = [
            'name'          => $name,
            'state'         => self::STATE_NEW,
            'time.start'    => 0.0,
            'time.total'    => 0.0,
            'iterations'    => 0,
            'round'         => $round,
        ];
    }

    public function __call(string $method, array $params = []): mixed
    {
        $timer = &$this->timer;
        $result = null;

        switch ($method) {
            case 'start':
            case 'go':
                if ($timer['state'] === self::STATE_STOPPED) {
                    $timer['time.total'] = 0.0;
                    $timer['iterations'] = 0;
                }

                $timer['state'] = self::STATE_RUNNING;
                $timer['time.start'] = microtime(true);
                $timer['iterations']++;
                $result = true;
                break;

            case 'pause':
                $timer['state'] = self::STATE_PAUSED;
                $timer['time.total'] += microtime(true) - $timer['time.start'];
                $result = number_format($timer['time.total'], $timer['round'], '.', '');
                break;

            case 'stop':
                $timer['state'] = self::STATE_STOPPED;
                $timer['time.total'] += microtime(true) - $timer['time.start'];
                $result = number_format($timer['time.total'], $timer['round'], '.', '');
                break;

            case 'get':
                $result = $timer['time.total'];
                break;

            case 'destroy':
                $timer = [];
                $result = true;
                break;

            case 'is_exists':
                $result = true;
                break;

            case 'get_state':
                $result = $timer['state'];
                break;
        }

        return $result;
    }

    public static function __callStatic(string $method, array $params = []): mixed
    {
        $result = null;

        $name = empty($params) ? self::DEFAULT_INTERNAL_NAME : $params[0];
        $name = self::resolveName($name);

        switch ($method) {
            case 'start':
                $round = $params[1] ?? self::DEFAULT_PRECISION;
                self::init($name, $round);
                self::go($name);
                break;

            case 'go':
                $timer = &self::$timers[$name];

                if ($timer['state'] === self::STATE_STOPPED) {
                    $timer['time.total'] = 0.0;
                    $timer['iterations'] = 0;
                }

                $timer['state'] = self::STATE_RUNNING;
                $timer['time.start'] = microtime(true);
                $timer['iterations']++;
                $result = $timer['time.start'];
                break;

            case 'pause':
                $timer = &self::$timers[$name];
                $timer['state'] = self::STATE_PAUSED;
                $timer['time.total'] += microtime(true) - $timer['time.start'];
                $result = number_format($timer['time.total'], $timer['round'], '.', '');
                break;

            case 'stop':
                $timer = &self::$timers[$name];
                $timer['state'] = self::STATE_STOPPED;
                $timer['time.total'] += microtime(true) - $timer['time.start'];
                $result = number_format($timer['time.total'], $timer['round'], '.', '');
                break;

            case 'stopAll':
                foreach (self::$timers as $n => $timer) {
                    if ($timer['iterations'] === 0) {
                        unset(self::$timers[$n]);
                        continue;
                    }
                    if (
                        self::$timers[$n]['state'] !== self::STATE_STOPPED
                        && self::$timers[$n]['state'] !== self::STATE_PAUSED
                    ) {
                        self::$timers[$n]['time.total'] += microtime(true) - self::$timers[$n]['time.start'];
                        self::$timers[$n]['state'] = self::STATE_STOPPED;
                    }
                }
                break;

            case 'get':
                $result = self::$timers[$name]['time.total'];
                break;

            case 'destroy':
                if (array_key_exists($name, self::$timers)) {
                    unset(self::$timers[$name]);
                    $result = true;
                } else {
                    $result = false;
                }
                break;

            case 'is_exists':
                $result = array_key_exists($name, self::$timers);
                break;

            case 'get_state':
                if (array_key_exists($name, self::$timers)) {
                    $result = self::$timers[$name]['state'];
                } else {
                    $result = self::STATE_UNDEFINED;
                }
                break;

            case 'get_all_timers':
                array_walk(self::$timers, function (&$item) {
                    unset($item['state'], $item['time.start'], $item['iterations']);
                    $item['time'] = $item['time.total'];
                    unset($item['time.total']);
                });
                $result = self::$timers;
                break;

            default:
                $result = false;
        }

        return $result;
    }

    private static function resolveName(?string $name): string
    {
        return (is_null($name) || $name === '')
            ? self::DEFAULT_INTERNAL_NAME
            : $name;
    }
}

# -eof- #
