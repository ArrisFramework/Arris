<?php

namespace Arris\Helpers;

/**
 * HTTP статус-коды согласно RFC 7231 и RFC 6585.
 *
 * Примеры использования:
 *  - HttpStatus::OK->value                          => 200
 *  - HttpStatus::OK->getStatusLine()                => "HTTP/1.1 200 OK"
 *  - HttpStatus::from(404)                          => HttpStatus::NOT_FOUND
 *  - HttpStatus::tryFrom(999)                       => null
 *  - HttpStatus::NOT_FOUND->isClientError()         => true
 */
enum HTTPStatus: int
{
    // 1xx Informational
    case CONTINUE = 100;
    case SWITCHING_PROTOCOLS = 101;
    case EARLY_HINTS = 103;

    // 2xx Success
    case OK = 200;
    case CREATED = 201;
    case ACCEPTED = 202;
    case NON_AUTHORITATIVE_INFORMATION = 203;
    case NO_CONTENT = 204;
    case RESET_CONTENT = 205;
    case PARTIAL_CONTENT = 206;

    // 3xx Redirection
    case MULTIPLE_CHOICES = 300;
    case MOVED_PERMANENTLY = 301;
    case FOUND = 302;
    case SEE_OTHER = 303;
    case NOT_MODIFIED = 304;
    case USE_PROXY = 305;
    case TEMPORARY_REDIRECT = 307;

    // 4xx Client Error
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case PAYMENT_REQUIRED = 402;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case NOT_ACCEPTABLE = 406;
    case PROXY_AUTHENTICATION_REQUIRED = 407;
    case REQUEST_TIMEOUT = 408;
    case CONFLICT = 409;
    case GONE = 410;
    case LENGTH_REQUIRED = 411;
    case PRECONDITION_FAILED = 412;
    case REQUEST_ENTITY_TOO_LARGE = 413;
    case REQUEST_URI_TOO_LARGE = 414;
    case UNSUPPORTED_MEDIA_TYPE = 415;
    case REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    case EXPECTATION_FAILED = 417;

    case IM_A_TEAPOT = 418; // RFC 2324 (шутка, но иногда используется)
    case TOO_MANY_REQUESTS = 429; // RFC 6585
    case LEGAL_OBSTACLES = 451; // RFC 7725

    // 5xx Server Error
    case INTERNAL_SERVER_ERROR = 500;
    case NOT_IMPLEMENTED = 501;
    case BAD_GATEWAY = 502;
    case SERVICE_UNAVAILABLE = 503;
    case GATEWAY_TIMEOUT = 504;

    /**
     * Возвращает текстовое описание статуса (Reason Phrase).
     * Генерируется динамически из имени кейса с обработкой редких исключений RFC.
     */
    public function getReasonPhrase(): string
    {
        return match ($this) {
            // Исключения, которые не соответствуют простому правилу ucwords(str_replace('_', ' ', ...))
            self::NON_AUTHORITATIVE_INFORMATION =>  'Non-Authoritative Information',
            self::REQUEST_TIMEOUT               =>  'Request Time-out',
            self::REQUEST_URI_TOO_LARGE         =>  'Request-URI Too Large',
            self::IM_A_TEAPOT                   =>  "I'm a teapot", // RFC 2324 (шутка, но для полноты)
            self::OK                            =>  "OK",

            // Для 95% случаев этого достаточно:
            // NOT_FOUND -> not found -> Not Found
            // INTERNAL_SERVER_ERROR -> internal server error -> Internal Server Error
            default => ucwords(strtolower(str_replace('_', ' ', $this->name))),
        };
    }

    /**
     * Возвращает полную строку HTTP-статуса для отправки в заголовке.
     *
     * @param string $httpVersion Версия HTTP (по умолчанию '1.1', можно '2' или '3')
     */
    public function getStatusLine(string $httpVersion = '1.1'): string
    {
        // Формат строго определён в RFC 7230 Section 3.1.2:
        // status-line = HTTP-version SP status-code SP reason-phrase
        return "HTTP/{$httpVersion} {$this->value} {$this->getReasonPhrase()}";
    }

    /**
     * Проверяет, является ли статус информационным (1xx).
     */
    public function isInformational(): bool
    {
        return $this->value >= 100 && $this->value < 200;
    }

    /**
     * Проверяет, является ли статус успешным (2xx).
     */
    public function isSuccess(): bool
    {
        return $this->value >= 200 && $this->value < 300;
    }

    /**
     * Проверяет, является ли статус редиректом (3xx).
     */
    public function isRedirection(): bool
    {
        return $this->value >= 300 && $this->value < 400;
    }

    /**
     * Проверяет, является ли статус ошибкой клиента (4xx).
     */
    public function isClientError(): bool
    {
        return $this->value >= 400 && $this->value < 500;
    }

    /**
     * Проверяет, является ли статус ошибкой сервера (5xx).
     */
    public function isServerError(): bool
    {
        return $this->value >= 500 && $this->value < 600;
    }

    /**
     * Проверяет, является ли статус ошибкой (4xx или 5xx).
     */
    public function isError(): bool
    {
        return $this->isClientError() || $this->isServerError();
    }

}