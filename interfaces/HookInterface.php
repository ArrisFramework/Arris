<?php
declare(strict_types=1);

namespace Arris;

interface HookInterface
{
    public static function init(): void;
    public static function on(string $eventName, callable $callback, int $priority = 100): void;
    public static function register(string $eventName, callable $callback, int $priority = 100): void;
    public static function once(string $eventName, callable $callback, int $priority = 100): void;
    public static function off(string $eventName, callable $callback): bool;
    public static function removeAllListeners(?string $eventName = null): void;
    public static function run(string $eventName, array $arguments = [], ?callable $continueCallBack = null): bool;
    public static function getListeners(string $eventName): array;
}