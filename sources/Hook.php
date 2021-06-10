<?php

namespace Arris;

// use Sabre\Event\EmitterInterface;
// use Sabre\Event\WildcardEmitter;

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

    public static function register(string $eventName, callable $callBack, int $priority = 100)
    {
        if (empty($eventName)) return;
        self::$emitter->on($eventName, $callBack, $priority);
    }

    public static function run(string $eventName, array $arguments = [], callable $continueCallBack = null)
    {
        if (empty($eventName)) return false;
        return self::$emitter->emit($eventName, $arguments, $continueCallBack);
    }

}

# -eof-