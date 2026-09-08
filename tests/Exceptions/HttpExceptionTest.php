<?php

declare(strict_types=1);

namespace Tests\Exceptions;

use Arris\Exceptions\HttpException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(HttpException::class)]
class HttpExceptionTest extends TestCase
{
    #[Test]
    public function extendsRuntimeException(): void
    {
        $e = new HttpException('oops');

        $this->assertInstanceOf(RuntimeException::class, $e);
    }

    #[Test]
    public function defaultsToBadRequest(): void
    {
        $e = new HttpException('message');

        $this->assertSame('message', $e->getMessage());
        $this->assertSame(400, $e->getStatusCode());
        $this->assertSame(400, $e->getCode());
        $this->assertNull($e->getPayload());
    }

    #[Test]
    public function carriesStatusCodeAndPayload(): void
    {
        $payload = ['debug' => 'trace'];
        $e = new HttpException('Not Found', 404, $payload);

        $this->assertSame(404, $e->getStatusCode());
        $this->assertSame(404, $e->getCode());
        $this->assertSame($payload, $e->getPayload());
    }

    #[Test]
    public function carriesPreviousException(): void
    {
        $previous = new RuntimeException('root');
        $e = new HttpException('wrapped', 500, null, $previous);

        $this->assertSame($previous, $e->getPrevious());
    }
}
