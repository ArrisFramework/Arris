<?php

declare(strict_types=1);

namespace Arris\Exceptions;

use RuntimeException;

/**
 * HTTP-исключение, бросаемое из слоя контроллеров.
 *
 * В отличие от голого RuntimeException несёт HTTP-статус и произвольный payload,
 * которые должны уйти в ответ клиенту. Позволяет центральному обработчику ошибок
 * отличить "контроллер прервал цепочку и хочет отдать HTTP-ответ" от настоящей
 * внутренней ошибки.
 */
class HttpException extends RuntimeException
{
    protected int $statusCode;

    protected mixed $payload;

    public function __construct(
        string $message = '',
        int $statusCode = 400,
        mixed $payload = null,
        ?\Throwable $previous = null
    ) {
        $this->statusCode = $statusCode;
        $this->payload    = $payload;

        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * HTTP-статус ответа.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Произвольные данные, приложенные к ошибке.
     */
    public function getPayload(): mixed
    {
        return $this->payload;
    }
}
