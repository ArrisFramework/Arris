<?php
declare(strict_types=1);

namespace Arris\Helpers;

/**
 * Нестандартные HTTP статус-коды.
 *
 * Коды, используемые веб-серверами, CDN, фреймворками и облачными провайдерами,
 * но НЕ определённые в официальных RFC (IETF/IANA).
 *
 * Примеры:
 *  - HTTPStatusExtended::NGINX_NO_RESPONSE->value           => 444
 *  - HTTPStatusExtended::CF_WEB_SERVER_DOWN->getReasonPhrase() => "Web Server Is Down"
 *  - HTTPStatusExtended::LARAVEL_PAGE_EXPIRED->isClientError() => true
 */
enum HTTPStatusExtended: int implements HTTPStatusExtendedInterface
{
    // =========================================================================
    // nginx
    // =========================================================================

    /** Сервер закрывает соединение без отправки ответа (защита от ботов) */
    case NGINX_NO_RESPONSE = 444;

    /** Неверный SSL-сертификат клиента */
    case NGINX_SSL_CERTIFICATE_ERROR = 495;

    /** Требуется SSL-сертификат клиента, но он не предоставлен */
    case NGINX_SSL_CERTIFICATE_REQUIRED = 496;

    /** HTTP-запрос отправлен на HTTPS-порт */
    case NGINX_HTTP_TO_HTTPS = 497;

    /** Клиент закрыл соединение до получения ответа от сервера */
    case NGINX_CLIENT_CLOSED_REQUEST = 499;

    // =========================================================================
    // Microsoft IIS
    // =========================================================================

    /** Сессия клиента истекла, требуется повторная авторизация */
    case IIS_LOGIN_TIMEOUT = 440;

    /** Сервер не может выполнить запрос из-за отсутствия обязательных данных */
    case IIS_RETRY_WITH = 449;

    /** Доступ заблокирован родительским контролем Windows */
    case IIS_BLOCKED_BY_PARENTAL_CONTROLS = 450;

    // =========================================================================
    // Cloudflare (52x — проблемы с origin-сервером)
    // =========================================================================

    /** Origin вернул пустой, неизвестный или неожиданный ответ */
    case CF_UNKNOWN_ERROR = 520;

    /** Origin отказал в TCP-соединении (сервер выключен или блокирует CF) */
    case CF_WEB_SERVER_DOWN = 521;

    /** Таймаут TCP-соединения с origin */
    case CF_CONNECTION_TIMED_OUT = 522;

    /** Origin недоступен (DNS не резолвится) */
    case CF_ORIGIN_UNREACHABLE = 523;

    /** TCP-соединение установлено, но origin не ответил вовремя */
    case CF_TIMEOUT_OCCURRED = 524;

    /** Не удалось согласовать SSL/TLS handshake с origin */
    case CF_SSL_HANDSHAKE_FAILED = 525;

    /** SSL-сертификат origin невалиден */
    case CF_INVALID_SSL_CERTIFICATE = 526;

    /** Cloudflare не смог зарезолвить DNS-имя origin */
    case CF_ORIGIN_DNS_ERROR = 530;

    // =========================================================================
    // AWS Elastic Load Balancing
    // =========================================================================

    /** Клиент закрыл соединение до истечения idle timeout */
    case AWS_CLIENT_CLOSED_CONNECTION = 460;

    /** X-Forwarded-For содержит более 30 IP-адресов */
    case AWS_TOO_MANY_FORWARDED_IPS = 463;

    /** Несовместимые версии протоколов между клиентом и origin */
    case AWS_INCOMPATIBLE_PROTOCOL = 464;

    /** Ошибка аутентификации на сервере за балансировщиком */
    case AWS_UNAUTHORIZED = 561;

    // =========================================================================
    // Apache / cPanel
    // =========================================================================

    /** Превышен лимит трафика (shared hosting) */
    case APACHE_BANDWIDTH_LIMIT_EXCEEDED = 509;

    /** Превышен лимит ресурсов аккаунта (CPU/RAM/processes) */
    case CPANEL_RESOURCE_LIMIT_REACHED = 508;

    // =========================================================================
    // Фреймворки и приложения
    // =========================================================================

    /** Laravel: CSRF-токен отсутствует или просрочен */
    case LARAVEL_PAGE_EXPIRED = 419;

    // =========================================================================
    // Прокси / Неформальные
    // =========================================================================

    /** Прокси: таймаут чтения из сети */
    case PROXY_NETWORK_READ_TIMEOUT = 598;

    /** Прокси: таймаут сетевого соединения */
    case PROXY_NETWORK_CONNECT_TIMEOUT = 599;

    /**
     * Возвращает текстовое описание статуса.
     * Все reason phrases заданы явно, так как автогенерация из имени кейса
     * некорректна для нестандартных кодов (префиксы NGINX_, CF_ и т.д.).
     */
    public function getReasonPhrase(): string
    {
        return match ($this) {
            // nginx
            self::NGINX_NO_RESPONSE             => 'No Response',
            self::NGINX_SSL_CERTIFICATE_ERROR   => 'SSL Certificate Error',
            self::NGINX_SSL_CERTIFICATE_REQUIRED => 'SSL Certificate Required',
            self::NGINX_HTTP_TO_HTTPS           => 'HTTP Request Sent to HTTPS Port',
            self::NGINX_CLIENT_CLOSED_REQUEST   => 'Client Closed Request',

            // IIS
            self::IIS_LOGIN_TIMEOUT              => 'Login Time-out',
            self::IIS_RETRY_WITH                 => 'Retry With',
            self::IIS_BLOCKED_BY_PARENTAL_CONTROLS => 'Blocked by Windows Parental Controls',

            // Cloudflare
            self::CF_UNKNOWN_ERROR         => 'Web Server Returned an Unknown Error',
            self::CF_WEB_SERVER_DOWN       => 'Web Server Is Down',
            self::CF_CONNECTION_TIMED_OUT  => 'Connection Timed Out',
            self::CF_ORIGIN_UNREACHABLE    => 'Origin Is Unreachable',
            self::CF_TIMEOUT_OCCURRED      => 'A Timeout Occurred',
            self::CF_SSL_HANDSHAKE_FAILED  => 'SSL Handshake Failed',
            self::CF_INVALID_SSL_CERTIFICATE => 'Invalid SSL Certificate',
            self::CF_ORIGIN_DNS_ERROR      => 'Origin DNS Error',

            // AWS ELB
            self::AWS_CLIENT_CLOSED_CONNECTION  => 'Client Closed Connection',
            self::AWS_TOO_MANY_FORWARDED_IPS   => 'Too Many Forwarded IPs',
            self::AWS_INCOMPATIBLE_PROTOCOL    => 'Incompatible Protocol Versions',
            self::AWS_UNAUTHORIZED             => 'Unauthorized',

            // Apache / cPanel
            self::APACHE_BANDWIDTH_LIMIT_EXCEEDED  => 'Bandwidth Limit Exceeded',
            self::CPANEL_RESOURCE_LIMIT_REACHED    => 'Resource Limit Is Reached',

            // Frameworks / Apps
            self::LARAVEL_PAGE_EXPIRED        => 'Page Expired',

            // Proxy
            self::PROXY_NETWORK_READ_TIMEOUT    => 'Network Read Timeout Error',
            self::PROXY_NETWORK_CONNECT_TIMEOUT => 'Network Connect Timeout Error',
        };
    }

    /**
     * Полная строка HTTP-статуса для заголовка.
     */
    public function getStatusLine(string $httpVersion = '1.1'): string
    {
        return "HTTP/{$httpVersion} {$this->value} {$this->getReasonPhrase()}";
    }

    public function isInformational(): bool { return $this->value >= 100 && $this->value < 200; }
    public function isSuccess(): bool     { return $this->value >= 200 && $this->value < 300; }
    public function isRedirection(): bool { return $this->value >= 300 && $this->value < 400; }
    public function isClientError(): bool { return $this->value >= 400 && $this->value < 500; }
    public function isServerError(): bool { return $this->value >= 500 && $this->value < 600; }
    public function isError(): bool       { return $this->isClientError() || $this->isServerError(); }
}