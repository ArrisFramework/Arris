<?php
declare(strict_types=1);

namespace Arris;

/**
 * Hook System / Event Emitter
 *
 * Объединяет логику EventEmitter и статический фасад Hook.
 * Поддерживает wildcards (*), приоритеты и однократные подписки (once).
 *
 * Использование в PHP:
 *   Hook::on('user:login', fn($user) => ...);
 *   Hook::run('user:login', [$user]);
 *
 * Использование в Smarty:
 *   {Hook::run('app:init')}
 *   или через registerClass: $smarty->registerClass('Hook', \Arris\Hook::class);
 */
class Hook implements HookInterface
{
    /**
     * Список обычных слушателей.
     * @var array<string, array<int, array{int, callable}>>
     */
    protected static array $listeners = [];

    /**
     * Список wildcard-слушателей.
     * @var array<string, array<int, array{int, callable}>>
     */
    protected static array $wildcardListeners = [];

    /**
     * Кэш отсортированных слушателей для быстрого emit.
     * Сбрасывается при любом изменении списка подписок.
     * @var array<string, callable[]>
     */
    protected static array $listenerIndex = [];

    /**
     * Инициализация (опционально, для совместимости со старым кодом).
     * В новой версии не требуется, так как свойства статические и инициализируются сразу.
     */
    public static function init(): void
    {
        self::$listeners = [];
        self::$wildcardListeners = [];
        self::$listenerIndex = [];
    }

    /**
     * Подписка на событие.
     * Поддерживает wildcard: 'user:*' подпишется на 'user:login', 'user:logout' и т.д.
     */
    public static function on(string $eventName, callable $callback, int $priority = 100): void
    {
        if ($eventName === '') {
            return;
        }

        self::resetIndex();

        if (str_ends_with($eventName, '*')) {
            $key = substr($eventName, 0, -1);
            self::$wildcardListeners[$key][] = [$priority, $callback];
        } else {
            self::$listeners[$eventName][] = [$priority, $callback];
        }
    }

    /**
     * Алиас для on(). Для совместимости со старым API.
     */
    public static function register(string $eventName, callable $callback, int $priority = 100): void
    {
        self::on($eventName, $callback, $priority);
    }

    /**
     * Подписка на событие ровно один раз.
     */
    public static function once(string $eventName, callable $callback, int $priority = 100): void
    {
        $wrapper = null;
        $wrapper = function () use ($eventName, $callback, &$wrapper) {
            self::off($eventName, $wrapper);
            return $callback(...func_get_args());
        };

        self::on($eventName, $wrapper, $priority);
    }

    /**
     * Отписка конкретного слушателя.
     */
    public static function off(string $eventName, callable $callback): bool
    {
        if ($eventName === '') {
            return false;
        }

        if (str_ends_with($eventName, '*')) {
            $key = substr($eventName, 0, -1);

            if (!isset(self::$wildcardListeners[$key])) {
                return false;
            }

            foreach (self::$wildcardListeners[$key] as $index => [$prio, $cb]) {
                if ($cb === $callback) {
                    unset(self::$wildcardListeners[$key][$index]);
                    self::resetIndex();
                    return true;
                }
            }
        } else {
            if (!isset(self::$listeners[$eventName])) {
                return false;
            }

            foreach (self::$listeners[$eventName] as $index => [$prio, $cb]) {
                if ($cb === $callback) {
                    unset(self::$listeners[$eventName][$index]);
                    self::resetIndex();
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Удаление всех слушателей.
     * Если eventName не передан — удаляются ВСЕ слушатели всех событий.
     */
    public static function removeAllListeners(?string $eventName = null): void
    {
        if ($eventName === null) {
            self::$listeners = [];
            self::$wildcardListeners = [];
        } elseif (str_ends_with($eventName, '*')) {
            unset(self::$wildcardListeners[substr($eventName, 0, -1)]);
        } else {
            unset(self::$listeners[$eventName]);
        }

        self::resetIndex();
    }

    /**
     * Запуск события.
     *
     * @param string        $eventName        Имя события
     * @param array         $arguments        Аргументы для слушателей
     * @param callable|null $continueCallBack Если вернет false — цепочка прерывается
     * @return bool false если слушатель явно вернул false, иначе true
     */
    public static function run(
        string $eventName,
        array $arguments = [],
        ?callable $continueCallBack = null
    ): bool {
        if ($eventName === '') {
            return false;
        }

        $listeners = self::getListeners($eventName);

        if (empty($listeners)) {
            return false;
        }

        $counter = count($listeners);

        foreach ($listeners as $listener) {
            --$counter;

            $result = $listener(...$arguments);

            if ($result === false) {
                return false;
            }

            if ($counter > 0 && $continueCallBack !== null && !$continueCallBack()) {
                break;
            }
        }

        return true;
    }

    /**
     * Возвращает отсортированный список слушателей для события.
     * Использует кэш (listenerIndex) для производительности.
     *
     * @return callable[]
     */
    public static function getListeners(string $eventName): array
    {
        if (!array_key_exists($eventName, self::$listenerIndex)) {
            $listeners = [];
            $priorities = [];

            // Обычные слушатели
            if (isset(self::$listeners[$eventName])) {
                foreach (self::$listeners[$eventName] as [$priority, $callback]) {
                    $priorities[] = $priority;
                    $listeners[] = $callback;
                }
            }

            // Wildcard-слушатели
            foreach (self::$wildcardListeners as $pattern => $wcs) {
                if ($pattern !== '' && str_starts_with($eventName, $pattern)) {
                    foreach ($wcs as [$priority, $callback]) {
                        $priorities[] = $priority;
                        $listeners[] = $callback;
                    }
                }
            }

            // Сортировка по приоритету (ascending: меньший номер = раньше)
            if (!empty($priorities)) {
                array_multisort($priorities, SORT_NUMERIC, SORT_ASC, $listeners);
            }

            self::$listenerIndex[$eventName] = $listeners;
        }

        return self::$listenerIndex[$eventName];
    }

    /**
     * Сброс кэша слушателей.
     * Вызывается при любом изменении списка подписок.
     */
    private static function resetIndex(): void
    {
        self::$listenerIndex = [];
    }
}