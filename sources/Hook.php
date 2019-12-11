<?php

namespace Arris;

use Sabre\Event\EmitterInterface;
use Sabre\Event\WildcardEmitter;



class Hook implements HookInterface
{
    /**
     * @var EmitterInterface
     */
    private static $emitter;

    public static function init()
    {
        self::$emitter = new WildcardEmitter();
    }

    public static function register(string $eventName, callable $callBack, int $priority = 100)
    {
        self::$emitter->on($eventName, $callBack, $priority);
    }

    public static function run(string $eventName, array $arguments = [], callable $continueCallBack = null):bool
    {
        return self::$emitter->emit($eventName, $arguments, $continueCallBack);
    }

}

# -eof-