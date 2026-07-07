<?php
declare(strict_types=1);

namespace Arris\Controllers;

use Arris\App;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Abstract Controller
 *
 * базовая функциональность: JSON-ответы, валидация, чтение тела запроса
 *
 * Provides common functionality for all API controllers.
 *
 * Сервисы приложения (pdo, redis, template, config и т.д.)
 * резолвятся через App\App (см. __get).
 */
abstract class AbstractController
{
    /**
     * Инстанс приложения.
     * В рантайме — App\App (наследник Arris\App).
     */
    protected App $app;

    /**
     * PSR-логгер
     */
    protected LoggerInterface $logger;

    /**
     * Опциональный презентер для вывода ответа.
     * Любой объект с методом present(array $payload, int $statusCode): void.
     */
    protected ?object $presenter = null;

    /**
     * Payload подготовленного ответа
     *
     * @var array|null
     */
    protected ?array $responsePayload = null;

    /**
     * HTTP-статус подготовленного ответа
     *
     * @var int
     */
    protected int $responseStatusCode = 200;

    /**
     * @param App|null $app Инстанс приложения (по умолчанию — \Arris\app())
     * @param LoggerInterface|null $logger PSR-логгер (по умолчанию NullLogger)
     * @param object|null $presenter Опциональный презентер
     */
    public function __construct(
        ?App $app = null,
        ?LoggerInterface $logger = null,
        ?object $presenter = null
    ) {
        $this->app = $app ?? \Arris\app();
        $this->logger = $logger ?? new NullLogger();
        $this->presenter = $presenter;
    }

    /**
     * Установить презентер для вывода ответа.
     *
     * @param object $presenter Объект с методом present(array $payload, int $statusCode): void
     */
    public function setPresenter(object $presenter): void
    {
        $this->presenter = $presenter;
    }

    /**
     * Send JSON success response.
     *
     * @param mixed $data Данные ответа
     * @param string $message Сообщение
     * @param int $statusCode HTTP-статус
     */
    protected function success(mixed $data = null, string $message = 'OK', int $statusCode = 200): void
    {
        $this->jsonResponse([
            'status'  => 'ok',
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    /**
     * Send JSON error response.
     *
     * Прерывает выполнение через throw.
     *
     * @param string $message Сообщение об ошибке
     * @param int $statusCode HTTP-статус
     * @param mixed $data Дополнительные данные
     *
     * @throws \RuntimeException
     */
    protected function error(string $message, int $statusCode = 400, mixed $data = null): never
    {
        $this->responsePayload = [
            'status'  => 'error',
            'message' => $message,
            'data'    => $data,
        ];
        $this->responseStatusCode = $statusCode;

        throw new \RuntimeException($message, $statusCode);
    }

    /**
     * Prepare JSON response and delegate to Presenter if connected.
     *
     * @param array $payload Данные ответа
     * @param int $statusCode HTTP-статус
     */
    protected function jsonResponse(array $payload, int $statusCode = 200): void
    {
        $this->responsePayload = $payload;
        $this->responseStatusCode = $statusCode;

        if ($this->presenter !== null) {
            $this->presenter->present($payload, $statusCode);
        }
    }

    /**
     * Get JSON body from request.
     *
     * @return array
     */
    protected function getJSONPayload(bool $is_associative = true): array
    {
        $input = file_get_contents('php://input');

        if (empty($input)) {
            return [];
        }

        $data = json_decode($input, $is_associative);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON Payload: ' . json_last_error_msg(), 400);
        }

        return $data ?? [];
    }

    /**
     * Get query parameter.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    protected function query(string $key, mixed $default = null): mixed
    {
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * Validate required fields in data array.
     *
     * @param array $data
     * @param array $required
     */
    protected function validateRequired(array $data, array $required): void
    {
        foreach ($required as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $this->error("Missing required field: {$field}", 422);
            }
        }
    }

    /**
     * Validate that a record exists.
     *
     * @param array|null $record
     * @param string $entity
     * @param int|string $id
     */
    protected function validateExists(?array $record, string $entity, int|string $id): void
    {
        if ($record === null) {
            $this->error("{$entity} not found: {$id}", 404);
        }
    }

    /**
     * Проверяет, был ли подготовлен ответ.
     *
     * @return bool
     */
    public function hasResponse(): bool
    {
        return $this->responsePayload !== null;
    }

    /**
     * Возвращает payload ответа для внешней обработки.
     *
     * @return array|null
     */
    public function getResponsePayload(): ?array
    {
        return $this->responsePayload;
    }

    /**
     * Возвращает HTTP-статус подготовленного ответа.
     *
     * @return int
     */
    public function getResponseStatusCode(): int
    {
        return $this->responseStatusCode;
    }

    /**
     * Магический доступ к сервисам приложения через свойства.
     *
     * Проксирует вызовы вида $this->pdo в $this->app->pdo().
     * Специальный ключ 'config' возвращает AppConfig.
     *
     * Пример:
     *  $this->pdo      → $this->app->pdo()
     *  $this->config   → $this->app->getConfig()
     *  $this->redis    → $this->app->redis()
     *  $this->template → $this->app->template()
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match (true) {
            $name === 'config'  => $this->app->getConfig(),
            method_exists($this->app, $name) => $this->app->{$name}(),
            default => throw new \RuntimeException(
                sprintf('Undefined property: %s::$%s', static::class, $name)
            )
        };
    }
}
