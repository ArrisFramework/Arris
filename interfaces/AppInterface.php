<?php

declare(strict_types=1);

namespace Arris;

use Arris\Core\Dot;

/**
 * Интерфейс ядра приложения Arris.
 *
 * Описывает контракт контейнера зависимостей, репозитория данных
 * и менеджера конфигурации.
 */
interface AppInterface
{
    /* ===================== DI & SERVICES =========================== */

    /**
     * Регистрирует сервис в контейнере.
     *
     * @param string $name Уникальное имя сервиса
     * @param mixed $definition Объект, Closure или массив конфигурации
     */
    public function addService(string $name, mixed $definition = null): void;

    /**
     * Возвращает инстанс сервиса по его имени.
     *
     * @param string $name Имя сервиса
     * @return mixed Инстанс сервиса или null
     */
    public function getService(string $name): mixed;

    /**
     * Проверяет, зарегистрирован ли сервис.
     *
     * @param string $name Имя сервиса
     * @return bool
     */
    public function isService(string $name): bool;

    /**
     * Возвращает тип зарегистрированного сервиса (class name, resource type или примитив).
     *
     * @param string $name Имя сервиса
     * @return string|null
     */
    public function getServiceType(string $name): ?string;

    /* ===================== REPOSITORY =========================== */

    /**
     * Массовое добавление данных в репозиторий.
     *
     * @param mixed $keys Массив данных или строковый ключ
     * @param mixed $value Значение (если $keys - строка)
     */
    public function add(mixed $keys, mixed $value = null): void;

    /**
     * Устанавливает значение по ключу (dot-notation поддерживается).
     *
     * @param string $key Ключ
     * @param mixed $data Значение
     */
    public function set(string $key, mixed $data = null): void;

    /**
     * Получает значение из репозитория.
     *
     * @param string|null $key Ключ (или null для получения всего репозитория)
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get(?string $key = null, mixed $default = null): mixed;

    /* ===================== CONFIG =========================== */

    /**
     * Получает значение из конфигурации.
     *
     * @param string|null $key Ключ конфигурации
     * @return mixed Значение или объект AppConfig, если ключ не передан
     */
    public function getConfig(?string $key = null): mixed;

    /**
     * Устанавливает значение в конфигурацию во время выполнения.
     *
     * @param string $key Ключ
     * @param mixed $value Значение
     */
    public function setConfig(string $key, mixed $value = null): void;

    /**
     * Массовое добавление/слияние данных в конфигурацию.
     *
     * @param array|Dot $config Массив или объект Dot с данными
     */
    public function addConfig(array|Dot $config): void;
}

