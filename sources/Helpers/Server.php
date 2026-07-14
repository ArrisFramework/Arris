<?php

namespace Arris\Helpers;

use InvalidArgumentException;

class Server implements ServerInterface
{
    /**
     * @param array $trustedProxies Список IP доверенных прокси.
     *   X-Forwarded-For и подобные заголовки учитываются, только если REMOTE_ADDR
     *   входит в этот список. Пустой массив = не доверять ни одному прокси.
     * @param bool $trustAnyProxy Если true — доверяем заголовкам прокси от любого IP,
     *   игнорируя проверку REMOTE_ADDR.
     */
    public static function getIP(array $trustedProxies = [], bool $trustAnyProxy = false): ?string
    {
        if (PHP_SAPI === 'cli') {
            return '127.0.0.1';
        }

        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;

        // 2. Доверяем заголовкам прокси если REMOTE_ADDR в списке доверенных ИЛИ доверяем любому прокси
        if ($trustAnyProxy || ($remoteAddr !== null && in_array($remoteAddr, $trustedProxies, true))) {
            $headers = [
                'HTTP_X_REAL_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_CLIENT_IP',
            ];

            // 3. Проверяем заголовки от прокси
            foreach ($headers as $header) {
                if (!empty($_SERVER[$header])) {
                    $ip = trim(explode(',', $_SERVER[$header])[0]);

                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }

        // 4. Если заголовков нет или они невалидны, берем стандартный REMOTE_ADDR
        if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            return $_SERVER['REMOTE_ADDR'];
        }

        // 5. Если ничего не подошло
        return null;
    }

    /**
     * Определяет, установлен ли защищённый HTTPS-соединение.
     * Поддерживает работу за балансировщиками (Nginx, AWS ELB, Cloudflare).
     *
     * @return bool true если соединение защищённое
     */
    public static function isSSL(): bool
    {
        // 1. Прямое HTTPS-соединение (Apache, IIS)
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        // 2. За балансировщиком/прокси (самый частый случай в современных инфраструктурах)
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }

        // 3. Альтернативные заголовки прокси (Nginx, AWS ELB, Microsoft ISA)
        if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }
        if (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && $_SERVER['HTTP_FRONT_END_HTTPS'] === 'on') {
            return true;
        }

        // 4. Порт 443 (если не определено иное)
        if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }

        return false;
    }

    /**
     * Компилирует редирект
     *
     * @param string $uri
     * @param int $code
     *
     * @return array
     */
    private static function compilerRedirect(string $uri, int $code = 302):array
    {
        // 1. Валидация HTTP-кода (только стандартные коды редиректа)
        $allowedCodes = [301, 302, 303, 307, 308];
        if (!in_array($code, $allowedCodes, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid redirect code: %d. Allowed: %s', $code, implode(', ', $allowedCodes))
            );
        }

        // 2. Формируем Location
        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            // Абсолютный URL — валидируем
            if (!filter_var($uri, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException("Invalid absolute URL: {$uri}");
            }
            $location = $uri;
        } else {
            // Относительный URI — формируем полный URL
            $scheme = self::isSSL() ? 'https' : 'http';

            // Поддержка X-Forwarded-Host для работы за балансировщиком
            $host = $_SERVER['HTTP_X_FORWARDED_HOST']
                ?? $_SERVER['HTTP_HOST']
                ?? 'localhost';

            $location = "{$scheme}://{$host}{$uri}";
        }

        // 3. Защита от Header Injection (CRLF-инъекции)
        $location = str_replace(["\r", "\n", '%0d', '%0a'], '', $location);

        return [
            'location'  =>  $location,
            'code'      =>  $code
        ];
    }

    /**
     * Выполняет HTTP-редирект.
     *
     * Примеры:
     *  - redirect('/dashboard')                    => 302 на текущий домен + /dashboard
     *  - redirect('https://example.com', 301)      => 301 на внешний URL
     *  - redirect('/login', 302, false)            => редирект без exit (для тестов)
     *
     * @param string $uri URI для редиректа (абсолютный URL или путь)
     * @param int $code HTTP-код статуса (301, 302, 303, 307, 308)
     * @param bool $terminate Завершать ли выполнение скрипта после редиректа
     * @throws InvalidArgumentException Если URI или код невалидны
     */
    public static function redirect(string $uri, int $code = 302, bool $terminate = true): void
    {
        [$location, $code] = self::compilerRedirect($uri, $code);

        // Отправляем заголовок
        header("Location: {$location}", true, $code);

        // Завершение скрипта (опционально)
        if ($terminate) {
            exit(0);
        }
    }

    /**
     * Возврат структуры для объекта Response.
     * @param string $uri
     * @param int $code
     *
     * @return array<'status', 'headers'>
     */
    public static function createRedirectResponse(string $uri, int $code = 302):array
    {
        [$location, $code] = self::compilerRedirect($uri, $code);

        return [
            'status' => $code,
            'headers' => ['Location' => $location],
        ];
    }

    /**
     * Проверяет, является ли строка корректным URL.
     * Поддерживает http/https/ftp протоколы и IDN-домены (интернациональные).
     *
     * Примеры:
     *  - isValidUrl('https://example.com')              => true
     *  - isValidUrl('http://пример.рф')                 => true (IDN)
     *  - isValidUrl('ftp://files.example.com:21/path')  => true
     *  - isValidUrl('not-a-url')                        => false
     *  - isValidUrl('https://')                         => false
     *
     * @param string $url URL для проверки
     * @param bool $strict Строгая проверка (требует наличие host)
     * @return bool true если URL валиден
     */
    public static function isValidUrl(string $url, bool $strict = true): bool
    {
        $url = trim($url);

        if ($url === '') {
            return false;
        }

        // 1. Конвертируем IDN-домены в ASCII (punycode)
        // FILTER_VALIDATE_URL не поддерживает юникод в доменах
        $url = self::convertIdnToAscii($url);

        // 2. Базовая валидация через нативный filter_var
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // 3. Строгая проверка: наличие host и протокола
        if ($strict) {
            $parsed = parse_url($url);

            if ($parsed === false) {
                return false;
            }

            // Требуем наличие scheme (http/https/ftp)
            if (!isset($parsed['scheme']) || !in_array(strtolower($parsed['scheme']), ['http', 'https', 'ftp'], true)) {
                return false;
            }

            // Требуем наличие host
            if (empty($parsed['host'])) {
                return false;
            }

            // Дополнительная проверка: host должен содержать точку (защита от http://localhost)
            // Если нужно разрешить localhost, уберите эту проверку
            if (!str_contains($parsed['host'], '.')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Конвертирует (интернациональный) IDN-домен в punycode (ASCII).
     *
     * @param string $url URL с возможным IDN-доменом
     *
     * @return string URL с конвертированным доменом
     */
    public static function convertIdnToAscii(string $url): string
    {
        // Проверяем наличие юникод-символов
        if (!preg_match('/[^\x00-\x7F]/', $url)) {
            return $url; // Нет юникода, возвращаем как есть
        }

        $parsed = parse_url($url);
        if ($parsed === false || empty($parsed['host'])) {
            return $url;
        }

        $host = $parsed['host'];

        // Проверяем, является ли host IP-адресом (IDN не нужен для IP)
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $url;
        }

        // Конвертируем домен в ASCII (punycode)
        $asciiHost = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46, $info);

        if ($asciiHost === false) {
            return $url; // Конвертация не удалась, возвращаем оригинал
        }

        // Собираем URL обратно с ASCII-доменом
        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
        $user = isset($parsed['user']) ? $parsed['user'] . (isset($parsed['pass']) ? ':' . $parsed['pass'] : '') . '@' : '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path = $parsed['path'] ?? '';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return $scheme . $user . $asciiHost . $port . $path . $query . $fragment;
    }

    /**
     * Преобразует структуру $_FILES в массив отдельных файлов.
     *
     * Входной формат (PHP):
     *  ['name' => ['f1.txt', 'f2.txt'], 'type' => ['text/plain', 'text/plain'], ...]
     *
     * Выходной формат (удобный):
     *  [
     *    ['name' => 'f1.txt', 'type' => 'text/plain', 'tmp_name' => '...', 'error' => 0, 'size' => 100],
     *    ['name' => 'f2.txt', 'type' => 'text/plain', 'tmp_name' => '...', 'error' => 0, 'size' => 200]
     *  ]
     *
     * Примеры:
     *  - rearrangeFilesPost($_FILES['files'])  => массив файлов
     *  - rearrangeFilesPost($_FILES['avatar']) => массив из одного файла
     *
     * @param array<string, mixed> $filePost Структура $_FILES для одного поля формы
     * @return array<int, array<string, mixed>> Массив файлов
     * @throws InvalidArgumentException Если структура невалидна
     */
    public static function rearrangeFilesPost(array $filePost, bool $filterErrors = false): array
    {
        // Валидация: проверяем наличие обязательных ключей
        $requiredKeys = ['name', 'type', 'tmp_name', 'error', 'size'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $filePost)) {
                throw new InvalidArgumentException(
                    sprintf('Missing required key "%s" in $_FILES structure.', $key)
                );
            }
        }

        // Обработка одиночного файла (не массива)
        if (!is_array($filePost['name'])) {
            // Это одиночный файл — возвращаем массив из одного элемента
            return [[
                'name' => $filePost['name'],
                'type' => $filePost['type'],
                'tmp_name' => $filePost['tmp_name'],
                'error' => $filePost['error'],
                'size' => $filePost['size'],
            ]];
        }

        // Множественная загрузка файлов
        $fileCount = count($filePost['name']);

        if ($fileCount === 0) {
            return [];
        }

        // Оптимизация: используем array_map вместо двойного цикла
        // Это работает быстрее и чище
        return array_map(
            fn(int $index): array => [
                'name' => $filePost['name'][$index] ?? null,
                'type' => $filePost['type'][$index] ?? null,
                'tmp_name' => $filePost['tmp_name'][$index] ?? null,
                'error' => $filePost['error'][$index] ?? null,
                'size' => $filePost['size'][$index] ?? null,
            ],
            range(0, $fileCount - 1)
        );
    }



}