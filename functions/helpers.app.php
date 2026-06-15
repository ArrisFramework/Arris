<?php

namespace Arris;

use App\App; // Привязка к приложению, а не к ядру!

if (!function_exists('Arris\config')) {
    /**
     * Глобальный хелпер для чтения конфига в шаблонах.
     * Поддерживает dot-notation: config('database.host')
     *
     * @param string|null $key Ключ конфигурации
     * @param mixed $default Значение по умолчанию, если ключ не найден
     * @return mixed
     */
    function config(?string $key = null, mixed $default = null): mixed
    {
        return App::getInstance()->getConfig($key) ?? $default;
    }
}

if (!function_exists('Arris\app')) {
    /**
     * Глобальный хелпер для репозитория (DI / Service Locator).
     *
     * @param string|array|null $key
     * @param mixed $value
     * @return mixed
     */
    function app(string|array|null $key = null, mixed $value = null): mixed
    {
        $app = App::getInstance();

        // 1. Если аргументов нет, возвращаем сам инстанс App (как в Laravel)
        if (func_num_args() === 0) {
            return $app;
        }

        // 2. Массовое добавление: app(['user' => $userObj])
        if (is_array($key)) {
            $app->add($key);
            return true;
        }

        // 3. Установка значения: app('pdo', new PDO(...))
        if (func_num_args() >= 2) {
            $app->set((string)$key, $value);
            return true;
        }

        // 4. Получение значения: app('pdo')
        return $app->get($key);
    }
}

# -eof- #

