<?php

namespace Arris\Helpers;

/**
 * HTTP статус-коды согласно RFC 7231 и RFC 6585.
 *
 * HTTP Status Codes & their meaning
 *
 *
 *
 * Примеры использования:
 *  - HttpStatus::OK->value                          => 200
 *  - HttpStatus::OK->getStatusLine()                => "HTTP/1.1 200 OK"
 *  - HttpStatus::from(404)                          => HttpStatus::NOT_FOUND
 *  - HttpStatus::tryFrom(999)                       => null
 *  - HttpStatus::NOT_FOUND->isClientError()         => true
 *
 * Source: https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
 */
enum HTTPStatus: int implements HTTPStatusInterface
{
    // 1xx Informational

    /**
     * 100 - Continue
     * The server has received the request headers and the client should proceed to send the request body
     * (in the case of a request for which a body needs to be sent; for example, a POST request). Sending a
     * large request body to a server after a request has been rejected for inappropriate headers would be
     * inefficient. To have a server check the request's headers, a client must send Expect: 100-continue as
     * a header in its initial request and receive a 100 Continue status code in response before sending the
     * body. If the client receives an error code such as 403 (Forbidden) or 405 (Method Not Allowed) then it
     * shouldn't send the request's body. The response 417 Expectation Failed indicates that the request should
     * be repeated without the Expect header as it indicates that the server doesn't support expectations
     * (this is the case, for example, of HTTP/1.0 servers).
     */
    case CONTINUE = 100;

    /**
     * 101 - Switching Protocols
     * The requester has asked the server to switch protocols and the server has agreed to do so.
     */
    case SWITCHING_PROTOCOLS = 101;

    /**
     * 102 - Processing
     * A WebDAV request may contain many sub-requests involving file operations, requiring a long time to complete
     * the request. This code indicates that the server has received and is processing the request, but no response
     * is available yet. This prevents the client from timing out and assuming the request was lost.
     */
    case PROCESSING = 102;

    /**
     * 103 - Early Hints
     * Used to return some response headers before file HTTP message.
     */
    case EARLY_HINTS = 103;

    // 2xx Success

    /**
     * 200 - OK
     * Standard response for successful HTTP requests. The actual response will depend on the request method used.
     * In a GET request, the response will contain an entity corresponding to the requested resource. In a POST
     * request, the response will contain an entity describing or containing the result of the action.
     */
    case OK = 200;

    /**
     * 201 - Created
     * The request has been fulfilled, resulting in the creation of a new resource.
     */
    case CREATED = 201;

    /**
     * 202 - Accepted
     * The request has been accepted for processing, but the processing has not been completed. The request might
     * or might not be eventually acted upon, and may be disallowed when processing occurs.
     */
    case ACCEPTED = 202;

    /**
     * 203 - Non Authoritative Info
     * The server is a transforming proxy (e.g. a Web accelerator) that received a 200 OK from its origin, but is
     * returning a modified version of the origin's response.
     */
    case NON_AUTHORITATIVE_INFORMATION = 203;

    /**
     * 204 - No Content
     * The server successfully processed the request and is not returning any content.
     */
    case NO_CONTENT = 204;

    /**
     * 205 - Reset Content
     * The server successfully processed the request and is not returning any content.
     */
    case RESET_CONTENT = 205;

    /**
     * 206 - Partial Content
     * The server successfully processed the request, but is not returning any content.
     * Unlike a 204 response, this response requires that the requester reset the document view.
     */
    case PARTIAL_CONTENT = 206;

    /**
     * 207 - Multi Status
     * The message body that follows is an XML message and can contain a number of separate response codes, depending
     * on how many sub-requests were made.
     */
    case MULTI_STATUS = 207;

    /**
     * 208 - Already Reported
     * The members of a DAV binding have already been enumerated in a preceding part of the (multistatus) response,
     * and are not being included again.
     */
    case ALREADY_REPORTED = 208;

    /**
     * 226 - IM Used
     * The server has fulfilled a request for the resource, and the response is a representation of the result of
     * one or more instance-manipulations applied to the current instance.
     */
    case IM_USED = 226;

    // 3xx Redirection
    case MULTIPLE_CHOICES = 300;
    case MOVED_PERMANENTLY = 301;
    case FOUND = 302;
    case SEE_OTHER = 303;
    case NOT_MODIFIED = 304;
    case USE_PROXY = 305;



    case TEMPORARY_REDIRECT = 307;

    // 4xx Client Error
    /**
     * 400 - Bad Request
     * The server cannot or will not process the request due to an apparent client error (e.g., malformed request
     * syntax, size too large, invalid request message framing, or deceptive request routing).
     */
    case BAD_REQUEST = 400;

    /**
     * 401 - Unauthorized
     * Similar to 403 Forbidden, but specifically for use when authentication is required and has failed or has not
     * yet been provided. The response must include a WWW-Authenticate header field containing a challenge applicable
     * to the requested resource. See Basic access authentication and Digest access authentication. 401 semantically
     * means "unauthenticated", i.e. the user does not have the necessary credentials.
     * Note: Some sites issue HTTP 401 when an IP address is banned from the website (usually the website domain)
     * and that specific address is refused permission to access a website.
     */
    case UNAUTHORIZED = 401;

    /**
     * 402 - Payment Required
     * Reserved for future use. The original intention was that this code might be used as part of some form of
     * digital cash or micropayment scheme, as proposed for example by GNU Taler, but that has not yet happened,
     * and this code is not usually used. Google Developers API uses this status if a particular developer has
     * exceeded the daily limit on requests. Stripe API uses this code for errors with processing credit cards.
     */
    case PAYMENT_REQUIRED = 402;

    /**
     * 403 - Forbidden
     * The request was valid, but the server is refusing action. The user might not have the necessary permissions
     * for a resource, or may need an account of some sort.
     */
    case FORBIDDEN = 403;

    /**
     * 404 - Not Found
     * The requested resource could not be found but may be available in the future. Subsequent requests by the
     * client are permissible.
     */
    case NOT_FOUND = 404;

    /**
     * 405 - Method Not Allowed
     * A request method is not supported for the requested resource; for example, a GET request on a form that
     * requires data to be presented via POST, or a PUT request on a read-only resource.
     */
    case METHOD_NOT_ALLOWED = 405;

    /**
     * 406 - Not Acceptable
     * The requested resource is capable of generating only content not acceptable according to the Accept headers
     * sent in the request. See Content negotiation.
     */
    case NOT_ACCEPTABLE = 406;

    /**
     * 407 - Proxy Authentication Required
     * The client must first authenticate itself with the proxy.
     */
    case PROXY_AUTHENTICATION_REQUIRED = 407;

    /**
     * 408 - Request Timeout
     * The server timed out waiting for the request. According to HTTP specifications: "The client did not produce
     * a request within the time that the server was prepared to wait. The client MAY repeat the request without
     * modifications at any later time."
     */
    case REQUEST_TIMEOUT = 408;

    /**
     * 409 - Conflict
     * Indicates that the request could not be processed because of conflict in the request, such as an edit
     * conflict between multiple simultaneous updates.
     */
    case CONFLICT = 409;

    /**
     * 410 - Gone
     * Indicates that the resource requested is no longer available and will not be available again. This should
     * be used when a resource has been intentionally removed and the resource should be purged. Upon receiving a
     * 410 status code, the client should not request the resource in the future. Clients such as search engines
     * should remove the resource from their indices. Most use cases do not require clients and search engines to
     * purge the resource, and a "404 Not Found" may be used instead.
     */
    case GONE = 410;

    /**
     * 411 - Length Required
     * The request did not specify the length of its content, which is required by the requested resource.
     */
    case LENGTH_REQUIRED = 411;

    /**
     * 412 - Precondition Failed
     * The server does not meet one of the preconditions that the requester put on the request.
     */
    case PRECONDITION_FAILED = 412;

    /**
     * 413 - Payload Too Large
     * The request is larger than the server is willing or able to process. Previously called
     * "Request Entity Too Large".
     */
    case REQUEST_ENTITY_TOO_LARGE = 413;

    case REQUEST_URI_TOO_LARGE = 414;
    case UNSUPPORTED_MEDIA_TYPE = 415;
    case REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    case EXPECTATION_FAILED = 417;

    /**
     * 418 - I'm a teapot
     * This code was defined in 1998 as one of the traditional IETF April Fools' jokes, in
     * RFC 2324, Hyper Text Coffee Pot Control Protocol, and is not expected to be implemented by actual HTTP servers.
     * The RFC specifies this code should be returned by teapots requested to brew coffee. This HTTP status is used
     * as an Easter egg in some websites, including Google.com.
     */
    case IM_A_TEAPOT = 418; // RFC 2324 (шутка, но иногда используется)

    //@todo
    case MISDIRECTED_REQUEST = 421;
    case UNPROCESSABLE_ENTITY = 422;
    case LOCKED = 423;
    case FAILED_DEPENDENCY = 424;
    case UPGRADE_REQUIRED = 426;
    case PRECONDITION_REQUIRED = 428;

    case TOO_MANY_REQUESTS = 429; // RFC 6585

    case REQUEST_HEADER_FIELDS_TOO_LARGE = 431;

    case LEGAL_OBSTACLES = 451; // RFC 7725

    // 5xx Server Error

    /**
     * 500 - Internal Server Error
     * A generic error message, given when an unexpected condition was encountered and no more specific message
     * is suitable.
     */
    case INTERNAL_SERVER_ERROR = 500;
    case NOT_IMPLEMENTED = 501;
    case BAD_GATEWAY = 502;
    case SERVICE_UNAVAILABLE = 503;
    case GATEWAY_TIMEOUT = 504;

    case HTTP_VERSION_NOT_SUPPORTED = 505;

    case VARIANT_ALSO_NEGOTIATES = 506;

    case INSUFFICIENT_STORAGE = 507;

    case LOOP_DETECTED = 508;

    case NOT_EXTENDED = 510;

    case NETWORK_AUTHENTICATION_REQUIRED = 511;

    /**
     * Возвращает текстовое описание статуса (Reason Phrase).
     * Генерируется динамически из имени кейса с обработкой редких исключений RFC.
     */
    public function getReasonPhrase(): string
    {
        return match ($this) {
            // Исключения, которые не соответствуют простому правилу ucwords(str_replace('_', ' ', ...))
            self::OK                              => 'OK',
            self::NON_AUTHORITATIVE_INFORMATION   => 'Non-Authoritative Information',
            self::IM_USED                         => 'IM Used',
            self::REQUEST_TIMEOUT                 => 'Request Timeout',
            self::REQUEST_URI_TOO_LARGE           => 'URI Too Long',
            self::REQUEST_ENTITY_TOO_LARGE        => 'Payload Too Large',
            self::UNPROCESSABLE_ENTITY            => 'Unprocessable Content',
            self::LEGAL_OBSTACLES                 => 'Unavailable For Legal Reasons',
            self::IM_A_TEAPOT                     => "I'm a Teapot",

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

    /**
     * Поддержка устаревших названий статусов.
     */
    public static function fromLegacy(int $code): ?self
    {
        return self::tryFrom($code);
    }

    /**
     * Метод для непосредственной отправки заголовка.
     *
     * @param string $httpVersion
     *
     * @return void
     */
    public function sendHeader(string $httpVersion = '1.1'): void
    {
        if (!headers_sent()) {
            header($this->getStatusLine($httpVersion), true, $this->value);
        }
    }

}