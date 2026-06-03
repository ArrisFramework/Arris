<?php

namespace Arris;

interface HookInterface {

    /**
     * Инициализирует механизм хуков.
     * В данный момент это просто заглушка.
     *
     * @return mixed
     */
    public static function init();

    /**
     * Регистрирует событие для хука
     *
     * @param string $eventName -- событие
     * @param callable $callBack -- коллбэк
     * @param int $priority -- приоритет
     * @return mixed
     */
    public static function register(string $eventName, callable $callBack, int $priority = 100);

    /**
     * Вызывает обработчики хука
     *
     * @param string $eventName -- событие
     * @param array $arguments   -- аргументы
     * @param callable|null $continueCallBack -- @todo
     * @return bool|null
     */
    public static function run(string $eventName, array $arguments = [], callable $continueCallBack = null);
}