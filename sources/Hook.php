<?php

namespace Arris;

use \Arris\Core\EventEmitter;
use \Arris\Core\EventEmitterInterface;

class Hook implements HookInterface
{
    /**
     * @var EventEmitterInterface
     */
    private static $emitter;

    public static function init()
    {
        self::$emitter = new EventEmitter();
    }

    public static function on(string $eventName, callable $callBack, int $priority = 100)
    {
        self::register($eventName, $callBack, $priority);
    }

    public static function off(string $eventName, callable $callBack)
    {
        self::$emitter->removeListener($eventName, $callBack);
    }

    public static function register(string $eventName, callable $callBack, int $priority = 100)
    {
        if (empty($eventName)) {
            return;
        }
        self::$emitter->on($eventName, $callBack, $priority);
    }

    public static function run(string $eventName, array $arguments = [], callable $continueCallBack = null): ?bool
    {
        if (empty($eventName)) {
            return false;
        }

        if (empty(self::$emitter->listeners($eventName))) {
            return false;
        }

        return self::$emitter->emit($eventName, $arguments, $continueCallBack);
    }

}

# -eof-