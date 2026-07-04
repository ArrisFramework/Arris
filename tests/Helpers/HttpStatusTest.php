<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Arris\Helpers\HTTPStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(HttpStatus::class)]
final class HttpStatusTest extends TestCase
{
    /**
     * Тестирует базовые значения, фразы и строки статуса.
     */
    #[DataProvider('provideStandardStatuses')]
    public function testStandardStatuses(int $code, string $expectedPhrase, string $expectedLine): void
    {
        $status = HttpStatus::from($code);

        $this->assertSame($code, $status->value);
        $this->assertSame($expectedPhrase, $status->getReasonPhrase());
        $this->assertSame($expectedLine, $status->getStatusLine());
        $this->assertSame($expectedLine, $status->getStatusLine('1.1')); // Проверка дефолтного аргумента
    }

    public static function provideStandardStatuses(): array
    {
        return [
            '200 OK (стандартная генерация)' => [
                200,
                'OK',
                'HTTP/1.1 200 OK'
            ],
            '404 Not Found (стандартная генерация)' => [
                404,
                'Not Found',
                'HTTP/1.1 404 Not Found'
            ],
            '408 Request Timeout (исключение из match)' => [
                408,
                'Request Timeout',
                'HTTP/1.1 408 Request Timeout'
            ],
            '500 Internal Server Error (стандартная генерация)' => [
                500,
                'Internal Server Error',
                'HTTP/1.1 500 Internal Server Error'
            ],
        ];
    }

    /**
     * Тестирует кастомные версии HTTP в getStatusLine.
     */
    #[DataProvider('provideHttpVersions')]
    public function testStatusLineWithDifferentVersions(int $code, string $version, string $expected): void
    {
        $this->assertSame($expected, HttpStatus::from($code)->getStatusLine($version));
    }

    public static function provideHttpVersions(): array
    {
        return [
            'HTTP/2' => [201, '2', 'HTTP/2 201 Created'],
            'HTTP/3' => [503, '3', 'HTTP/3 503 Service Unavailable'],
        ];
    }

    /**
     * Тестирует вспомогательные методы категорий (isSuccess, isError и т.д.).
     */
    #[DataProvider('provideCategoryChecks')]
    public function testCategoryHelpers(int $code, bool $isSuccess, bool $isError): void
    {
        $status = HttpStatus::from($code);

        $this->assertSame($isSuccess, $status->isSuccess());
        $this->assertSame($isError, $status->isError());
    }

    public static function provideCategoryChecks(): array
    {
        return [
            '200 - успех, не ошибка' => [200, true, false],
            '301 - не успех, не ошибка' => [301, false, false],
            '404 - не успех, ошибка'   => [404, false, true],
            '500 - не успех, ошибка'   => [500, false, true],
        ];
    }

    /**
     * Тестирует безопасное получение Enum из невалидного кода.
     */
    public function testTryFromWithInvalidCodeReturnsNull(): void
    {
        $this->assertNull(HttpStatus::tryFrom(999));
        $this->assertNull(HttpStatus::tryFrom(0));
    }
}