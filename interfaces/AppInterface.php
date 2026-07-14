<?php

declare(strict_types=1);

namespace Arris;

use Arris\Core\Dot;

/**
 * Интерфейс ядра микрофреймворка Arris.
 *
 * Описывает контракт контейнера зависимостей, репозитория данных
 * и менеджера конфигурации.
 */
interface AppInterface
{
    /* ==================== ОСНОВНЫЕ МЕТОДЫ APP ===================== */

    public static function getInstance(array $config_files = [], array $options = [], array $services = []): static;

    public static function factory(array $config_files = [], array $options = [], array $services = []): static;

    /* ====================== AppConfig ====================== */

    // Хелпер
    public static function config(?string $key = null, mixed $value = null, mixed $default = null): mixed;

    // Статические методы
    public static function fromConfig(string $key, mixed $default = null):mixed;

    public static function toConfig(string $key, mixed $value = null):void;

    public static function theConfig():AppConfig;

    // Динамические методы работы с инстансом App.

    public function getConfig(?string $key = null, mixed $default = null): mixed;

    public function setConfig(string $key, mixed $value = null): static;

    public function hasConfig(string $key): bool;

    public function removeConfig(string $key): void;

    public function replaceConfig(array $config): void;

    public function addConfig(array|Dot $config): static;

    public function allConfig(): array;

    /* ===================== ОПЦИИ (Репозиторий опций App) =========================== */

    public function add(mixed $keys, mixed $value = null): static;

    public function set(string $key, mixed $data = null): static;

    public function get(?string $key = null, mixed $default = null): mixed;

    public function has(string $key): bool;

    public function remove(string $key): void;

    public function all(): array;


    /* ===================== DI & SERVICES =========================== */

    public function addService(string $name, mixed $definition = null): void;

    public function getService(string $name): mixed;

    public function isService(string $name): bool;

    public function getServiceType(string $name): ?string;

    /* =============================== MAGIC =============================== */

    public function __invoke(?string $key = null, mixed $data = null): mixed;

    public function __set(string $key, mixed $value): void;
}

