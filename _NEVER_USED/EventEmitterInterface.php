<?php

namespace Arris\Core;

interface EventEmitterInterface
{
    public function on(string $eventName, callable $callBack, int $priority = 100);
    public function once(string $eventName, callable $callBack, int $priority = 100);
    public function emit(string $eventName, array $arguments = [], callable $continueCallBack = null): bool;
    public function listeners(string $eventName): array;
    public function removeListener(string $eventName, callable $listener): bool;
    public function removeAllListeners(string $eventName = null);
}

# -eof-
