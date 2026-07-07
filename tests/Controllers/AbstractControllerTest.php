<?php
declare(strict_types=1);

namespace Tests\Controllers;

use Arris\App;
use Arris\Controllers\AbstractController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

#[CoversClass(AbstractController::class)]
class AbstractControllerTest extends TestCase
{
    private App $app;

    protected function setUp(): void
    {
        App::reset();
        $this->app = App::getInstance();
    }

    #[Test]
    public function constructorInjectsDependencies(): void
    {
        $controller = new ControllerTester(app: $this->app);

        $this->assertFalse($controller->hasResponse());
        $this->assertNull($controller->getResponsePayload());
        $this->assertSame(200, $controller->getResponseStatusCode());
    }

    #[Test]
    public function constructorWithCustomLogger(): void
    {
        $logger = new NullLogger();
        $controller = new ControllerTester(app: $this->app, logger: $logger);

        $this->assertSame($logger, $controller->getLogger());
    }

    #[Test]
    public function successStoresPayload(): void
    {
        $controller = new ControllerTester(app: $this->app);
        $controller->callSuccess(data: ['id' => 1], message: 'Created', statusCode: 201);

        $this->assertTrue($controller->hasResponse());
        $this->assertSame(201, $controller->getResponseStatusCode());
        $this->assertSame([
            'status'  => 'ok',
            'message' => 'Created',
            'data'    => ['id' => 1],
        ], $controller->getResponsePayload());
    }

    #[Test]
    public function successDefaults(): void
    {
        $controller = new ControllerTester(app: $this->app);
        $controller->callSuccess();

        $this->assertTrue($controller->hasResponse());
        $this->assertSame(200, $controller->getResponseStatusCode());
        $this->assertSame([
            'status'  => 'ok',
            'message' => 'OK',
            'data'    => null,
        ], $controller->getResponsePayload());
    }

    #[Test]
    public function errorStoresPayloadAndThrows(): void
    {
        $controller = new ControllerTester(app: $this->app);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not Found');
        $this->expectExceptionCode(404);

        try {
            $controller->callError(message: 'Not Found', statusCode: 404, data: ['debug' => 'trace']);
        } catch (RuntimeException $e) {
            $this->assertTrue($controller->hasResponse());
            $this->assertSame(404, $controller->getResponseStatusCode());
            $this->assertSame([
                'status'  => 'error',
                'message' => 'Not Found',
                'data'    => ['debug' => 'trace'],
            ], $controller->getResponsePayload());

            throw $e;
        }
    }

    #[Test]
    public function jsonResponseDelegatesToPresenter(): void
    {
        $presenter = new TestPresenter();
        $controller = new ControllerTester(app: $this->app, presenter: $presenter);

        $payload = ['status' => 'ok', 'message' => 'OK', 'data' => null];
        $controller->callJsonResponse(payload: $payload, statusCode: 200);

        $this->assertTrue($presenter->called);
        $this->assertSame($payload, $presenter->lastPayload);
        $this->assertSame(200, $presenter->lastStatusCode);
    }

    #[Test]
    public function jsonResponseWithoutPresenterStoresSilently(): void
    {
        $controller = new ControllerTester(app: $this->app);
        $controller->callJsonResponse(
            payload: ['status' => 'ok', 'data' => ['foo' => 'bar']],
            statusCode: 201
        );

        $this->assertTrue($controller->hasResponse());
        $this->assertSame(['status' => 'ok', 'data' => ['foo' => 'bar']], $controller->getResponsePayload());
        $this->assertSame(201, $controller->getResponseStatusCode());
    }

    #[Test]
    public function setPresenterAfterConstructionWorks(): void
    {
        $controller = new ControllerTester(app: $this->app);
        $presenter = new TestPresenter();

        $controller->setPresenter($presenter);
        $controller->callSuccess(data: 'test');

        $this->assertTrue($presenter->called);
    }

    #[Test]
    public function queryReturnsRequestParameter(): void
    {
        $_REQUEST['test_key'] = 'test_value';
        $controller = new ControllerTester(app: $this->app);

        $this->assertSame('test_value', $controller->callQuery('test_key'));

        unset($_REQUEST['test_key']);
    }

    #[Test]
    public function queryReturnsDefaultForMissingKey(): void
    {
        $controller = new ControllerTester(app: $this->app);

        $this->assertSame('fallback', $controller->callQuery('nonexistent', 'fallback'));
        $this->assertNull($controller->callQuery('nonexistent'));
    }

    #[Test]
    public function validateRequiredPassesWhenAllFieldsPresent(): void
    {
        $controller = new ControllerTester(app: $this->app);

        $controller->callValidateRequired(
            data: ['name' => 'John', 'email' => 'john@test.com'],
            required: ['name', 'email']
        );

        $this->assertFalse($controller->hasResponse());
    }

    #[Test]
    public function validateRequiredThrowsOnMissingField(): void
    {
        $controller = new ControllerTester(app: $this->app);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required field: email');

        $controller->callValidateRequired(
            data: ['name' => 'John'],
            required: ['name', 'email']
        );
    }

    #[Test]
    public function validateRequiredThrowsOnEmptyString(): void
    {
        $controller = new ControllerTester(app: $this->app);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required field: name');

        $controller->callValidateRequired(
            data: ['name' => ''],
            required: ['name']
        );
    }

    #[Test]
    public function validateRequiredThrowsOnWhitespaceOnly(): void
    {
        $controller = new ControllerTester(app: $this->app);

        $this->expectException(RuntimeException::class);

        $controller->callValidateRequired(
            data: ['name' => '   '],
            required: ['name']
        );
    }

    #[Test]
    public function validateExistsPassesWhenRecordNotNull(): void
    {
        $controller = new ControllerTester(app: $this->app);

        $controller->callValidateExists(
            record: ['id' => 1, 'name' => 'Test'],
            entity: 'User',
            id: 1
        );

        $this->assertFalse($controller->hasResponse());
    }

    #[Test]
    public function validateExistsThrowsOnNullRecord(): void
    {
        $controller = new ControllerTester(app: $this->app);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User not found: 42');

        $controller->callValidateExists(
            record: null,
            entity: 'User',
            id: 42
        );
    }

    #[Test]
    public function magicGetConfigReturnsAppConfig(): void
    {
        $this->app->setConfig('app.test', 'value');
        $controller = new ControllerTester(app: $this->app);

        $config = $controller->config;
        $this->assertInstanceOf(\Arris\AppConfig::class, $config);
        $this->assertSame('value', $config->get('app.test'));
    }

    #[Test]
    public function magicGetDelegatesToAppMethod(): void
    {
        $this->app->setConfig('custom.prop', 'from_app');
        $controller = new ControllerTester(app: $this->app);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Undefined property');

        $result = $controller->nonExistentProperty;
    }

    #[Test]
    public function responseAccessorsAfterMultipleCalls(): void
    {
        $controller = new ControllerTester(app: $this->app);

        $controller->callSuccess(data: 'first');
        $this->assertSame('ok', $controller->getResponsePayload()['status']);

        $controller->callSuccess(data: 'second');
        $this->assertSame('second', $controller->getResponsePayload()['data']);
    }

    #[Test]
    public function recordsLoggerAsService(): void
    {
        $controller = new ControllerTester(app: $this->app);

        $this->assertInstanceOf(NullLogger::class, $controller->getLogger());
    }

    #[Test]
    public function errorWithDefaultStatusCode(): void
    {
        $controller = new ControllerTester(app: $this->app);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(400);

        $controller->callError(message: 'Bad Request');
    }
}


/**
 * Конкретная реализация AbstractController для тестов.
 * Открывает protected-методы для вызова из тестов.
 */
class ControllerTester extends AbstractController
{
    public function callSuccess(mixed $data = null, string $message = 'OK', int $statusCode = 200): void
    {
        $this->success($data, $message, $statusCode);
    }

    public function callError(string $message, int $statusCode = 400, mixed $data = null): never
    {
        $this->error($message, $statusCode, $data);
    }

    public function callJsonResponse(array $payload, int $statusCode = 200): void
    {
        $this->jsonResponse($payload, $statusCode);
    }

    public function callGetJsonBody(): array
    {
        return $this->getJsonBody();
    }

    public function callQuery(string $key, mixed $default = null): mixed
    {
        return $this->query($key, $default);
    }

    public function callValidateRequired(array $data, array $required): void
    {
        $this->validateRequired($data, $required);
    }

    public function callValidateExists(?array $record, string $entity, int|string $id): void
    {
        $this->validateExists($record, $entity, $id);
    }

    public function getLogger(): \Psr\Log\LoggerInterface
    {
        return $this->logger;
    }
}


/**
 * Тестовый презентер, записывающий последний вызов.
 */
class TestPresenter
{
    public array $lastPayload = [];
    public int $lastStatusCode = 0;
    public bool $called = false;

    public function present(array $payload, int $statusCode): void
    {
        $this->lastPayload = $payload;
        $this->lastStatusCode = $statusCode;
        $this->called = true;
    }
}
